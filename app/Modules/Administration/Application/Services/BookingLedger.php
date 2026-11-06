<?php

namespace App\Modules\Administration\Application\Services;

use App\Modules\Booking\Domain\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Money figures for the admin console, derived from bookings alone.
 *
 * The Payments module has no ledger tables, so the booking rows are the
 * record. Three rules hold everywhere here:
 *
 *  - Amounts are never added across currencies; every total is per currency.
 *  - Gross booking value counts what guests bought: top-level bookings only
 *    (package_booking_id IS NULL). A package is one parent booking plus a
 *    child booking per component, so counting children too would count the
 *    package's money twice.
 *  - Provider payables come from the bookings a provider fulfils: standalone
 *    listing bookings and package component (child) bookings. A package
 *    parent's bookable is the package, which belongs to an agent, not a
 *    provider, so it never resolves to a provider.
 */
class BookingLedger
{
    /** Booking statuses whose money a provider is owed. */
    public const PAYABLE_STATUSES = ['confirmed', 'checked_in', 'checked_out'];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked in',
        'checked_out' => 'Completed (checked out)',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    public const PAYMENT_METHODS = [
        'mpesa' => 'M-Pesa',
        'credit_card' => 'Card',
        'bank_transfer' => 'Bank transfer',
        'paypal' => 'PayPal',
        'other' => 'Other',
    ];

    public const LISTING_TYPES = [
        'hotel' => 'Hotel',
        'villa' => 'Villa',
        'bed_and_breakfast' => 'B&B',
        'room_type' => 'Room type',
        'activity' => 'Experience',
        'experience' => 'Experience (legacy alias)',
        'activity-option' => 'Experience option',
        'package' => 'Package',
    ];

    /**
     * SQL resolving bookings.provider_id from the bookable, for every
     * bookable type a provider owns.
     */
    public static function providerIdSql(string $table = 'bookings'): string
    {
        return "(CASE {$table}.bookable_type
            WHEN 'hotel' THEN (SELECT provider_id FROM hotels WHERE hotels.id = {$table}.bookable_id)
            WHEN 'villa' THEN (SELECT provider_id FROM villas WHERE villas.id = {$table}.bookable_id)
            WHEN 'bed_and_breakfast' THEN (SELECT provider_id FROM bed_and_breakfasts WHERE bed_and_breakfasts.id = {$table}.bookable_id)
            WHEN 'activity' THEN (SELECT provider_id FROM activities WHERE activities.id = {$table}.bookable_id)
            WHEN 'experience' THEN (SELECT provider_id FROM activities WHERE activities.id = {$table}.bookable_id)
            WHEN 'activity-option' THEN (SELECT a.provider_id FROM activity_options o JOIN activities a ON a.id = o.activity_id WHERE o.id = {$table}.bookable_id)
            WHEN 'room_type' THEN (SELECT h.provider_id FROM room_types rt JOIN hotels h ON h.id = rt.hotel_id WHERE rt.id = {$table}.bookable_id)
            WHEN 'room' THEN (SELECT h.provider_id FROM rooms r JOIN hotels h ON h.id = r.hotel_id WHERE r.id = {$table}.bookable_id)
        END)";
    }

    /**
     * SQL resolving the agent behind a booking: the package's agent for a
     * package booking, or the parent package's agent for a component.
     */
    public static function agentIdSql(string $table = 'bookings'): string
    {
        return "(CASE
            WHEN {$table}.bookable_type = 'package' THEN (SELECT agent_id FROM packages WHERE packages.id = {$table}.bookable_id)
            WHEN {$table}.package_booking_id IS NOT NULL THEN (
                SELECT p.agent_id FROM bookings parent JOIN packages p ON p.id = parent.bookable_id
                WHERE parent.id = {$table}.package_booking_id AND parent.bookable_type = 'package')
        END)";
    }

    /**
     * @param  'created_at'|'check_in_date'  $basis
     */
    public function scoped(?string $from, ?string $to, string $basis = 'created_at'): Builder
    {
        $basis = in_array($basis, ['created_at', 'check_in_date'], true) ? $basis : 'created_at';

        return Booking::query()
            ->when($from, fn (Builder $q) => $q->where("bookings.{$basis}", '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where("bookings.{$basis}", '<=', CarbonImmutable::parse($to)->endOfDay()));
    }

    /**
     * Gross booking value per currency: top-level, not cancelled.
     *
     * @return Collection<int, object{currency: string, bookings: int, gross: string}>
     */
    public function grossByCurrency(?string $from, ?string $to, string $basis = 'created_at'): Collection
    {
        return $this->scoped($from, $to, $basis)
            ->whereNull('package_booking_id')
            ->where('status', '!=', 'cancelled')
            ->toBase()
            ->selectRaw('currency, count(*) as bookings, coalesce(sum(total_amount), 0) as gross')
            ->selectRaw("coalesce(sum(total_amount) filter (where payment_status = 'paid'), 0) as paid")
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();
    }

    /**
     * What each provider is owed, per currency, from confirmed and completed
     * bookings they fulfil (standalone and package components).
     *
     * @return Collection<int, object>
     */
    public function payablesByProvider(?string $from, ?string $to, string $basis = 'created_at'): Collection
    {
        $provider = self::providerIdSql();

        return DB::query()
            ->fromSub(
                $this->scoped($from, $to, $basis)
                    ->where('bookable_type', '!=', 'package')
                    ->whereIn('status', self::PAYABLE_STATUSES)
                    ->toBase()
                    ->selectRaw("{$provider} as provider_id, currency, total_amount, payment_status, package_booking_id"),
                'b'
            )
            ->leftJoin('providers', 'providers.id', '=', 'b.provider_id')
            ->selectRaw('b.provider_id, providers.company_name, b.currency, count(*) as bookings')
            ->selectRaw('count(*) filter (where b.package_booking_id is not null) as package_components')
            ->selectRaw('coalesce(sum(b.total_amount), 0) as payable')
            ->selectRaw("coalesce(sum(b.total_amount) filter (where b.payment_status = 'paid'), 0) as paid_in")
            ->groupBy('b.provider_id', 'providers.company_name', 'b.currency')
            ->orderBy('providers.company_name')
            ->orderBy('b.currency')
            ->get();
    }

    /**
     * Bookings behind one provider's payable, for the drill-down.
     */
    public function providerBookings(int $providerId, ?string $from, ?string $to, string $basis = 'created_at'): Collection
    {
        return $this->scoped($from, $to, $basis)
            ->where('bookable_type', '!=', 'package')
            ->whereIn('status', self::PAYABLE_STATUSES)
            ->whereRaw(self::providerIdSql().' = ?', [$providerId])
            ->with('packageBooking:id,confirmation_code')
            ->orderBy('check_in_date')
            ->get(['id', 'confirmation_code', 'bookable_type', 'bookable_id', 'check_in_date', 'check_out_date', 'status', 'payment_status', 'currency', 'total_amount', 'package_booking_id']);
    }

    /**
     * Agent commission per agent and currency. Cancelled bookings earn none.
     *
     * @return Collection<int, object>
     */
    public function agentCommission(?string $from, ?string $to, string $basis = 'created_at'): Collection
    {
        $agent = self::agentIdSql();

        return DB::query()
            ->fromSub(
                $this->scoped($from, $to, $basis)
                    ->whereNotNull('agent_commission')
                    ->where('status', '!=', 'cancelled')
                    ->toBase()
                    ->selectRaw("{$agent} as agent_id, currency, agent_commission, total_amount"),
                'b'
            )
            ->leftJoin('agents', 'agents.id', '=', 'b.agent_id')
            ->selectRaw("b.agent_id, coalesce(nullif(agents.agency_name, ''), trim(concat(agents.first_name, ' ', agents.last_name))) as agent_name, b.currency")
            ->selectRaw('count(*) as bookings, coalesce(sum(b.agent_commission), 0) as commission, coalesce(sum(b.total_amount), 0) as booked')
            ->groupBy('b.agent_id', 'agents.agency_name', 'agents.first_name', 'agents.last_name', 'b.currency')
            ->orderBy('agent_name')
            ->get();
    }

    /**
     * Cancelled and refunded top-level bookings per currency.
     *
     * @return Collection<int, object>
     */
    public function cancellationsAndRefunds(?string $from, ?string $to, string $basis = 'created_at'): Collection
    {
        return $this->scoped($from, $to, $basis)
            ->whereNull('package_booking_id')
            ->where(fn (Builder $q) => $q->where('status', 'cancelled')->orWhere('payment_status', 'refunded'))
            ->toBase()
            ->selectRaw('currency')
            ->selectRaw("count(*) filter (where status = 'cancelled') as cancelled")
            ->selectRaw("coalesce(sum(total_amount) filter (where status = 'cancelled'), 0) as cancelled_value")
            ->selectRaw("count(*) filter (where payment_status = 'refunded') as refunded")
            ->selectRaw("coalesce(sum(total_amount) filter (where payment_status = 'refunded'), 0) as refunded_value")
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();
    }
}
