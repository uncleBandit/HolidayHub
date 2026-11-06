<?php

namespace App\Modules\Administration\Filament\Pages;

use App\Modules\Administration\Application\Services\BookingLedger;
use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Shared\Support\Currencies;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Revenue and what is owed to whom, derived from bookings.
 *
 * Every figure is per currency; nothing is summed across currencies. See
 * BookingLedger for the counting rules (package children are excluded from
 * gross value and included in provider payables).
 */
class RevenueAndPayouts extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Revenue & payouts';

    protected static ?string $title = 'Revenue & payouts';

    protected static ?string $slug = 'revenue-and-payouts';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Bookings & money';

    protected string $view = 'administration::filament.pages.revenue-and-payouts';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    /** Which date the range applies to: when booked, or when the stay starts. */
    #[Url]
    public string $basis = 'created_at';

    /** Provider whose bookings are expanded below the payables table. */
    #[Url]
    public ?int $provider = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('payments.view') === true;
    }

    public function mount(): void
    {
        $this->from ??= now()->startOfYear()->toDateString();
        $this->to ??= now()->toDateString();
    }

    public function updatedBasis(string $value): void
    {
        $this->basis = in_array($value, ['created_at', 'check_in_date'], true) ? $value : 'created_at';
    }

    public function selectProvider(?int $providerId): void
    {
        $this->provider = $this->provider === $providerId ? null : $providerId;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    protected function getViewData(): array
    {
        $ledger = app(BookingLedger::class);
        $payables = $ledger->payablesByProvider($this->from, $this->to, $this->basis);

        return [
            'gross' => $ledger->grossByCurrency($this->from, $this->to, $this->basis),
            'payables' => $payables,
            'commission' => $ledger->agentCommission($this->from, $this->to, $this->basis),
            'cancellations' => $ledger->cancellationsAndRefunds($this->from, $this->to, $this->basis),
            'providerBookings' => $this->provider ? $ledger->providerBookings($this->provider, $this->from, $this->to, $this->basis) : collect(),
            'providerName' => $this->provider ? $payables->firstWhere('provider_id', $this->provider)?->company_name : null,
            'providerBookingsUrl' => $this->provider ? $this->bookingsUrl($this->provider) : null,
            'money' => fn ($amount, ?string $currency): string => Currencies::format($amount, $currency, 2),
        ];
    }

    /** The bookings list filtered to one provider and this page's date range. */
    public function bookingsUrl(int $providerId): string
    {
        return BookingOperationsResource::getUrl('index', ['filters' => [
            'provider' => ['value' => $providerId],
            $this->basis => array_filter(['from' => $this->from, 'until' => $this->to]),
        ]]);
    }

    private function export(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $ledger = app(BookingLedger::class);
        [$from, $to, $basis] = [$this->from, $this->to, $this->basis];

        app(AuditRecorder::class)->record('payments.report_exported', null, auth()->user(), meta: compact('from', 'to', 'basis'));

        return response()->streamDownload(function () use ($ledger, $from, $to, $basis): void {
            $out = fopen('php://output', 'w');
            self::csvRow($out, ['Revenue & payouts', "{$basis} from {$from} to {$to}", 'Amounts are per currency and never summed across currencies']);

            self::csvRow($out, []);
            self::csvRow($out, ['Gross booking value', 'currency', 'bookings', 'gross', 'paid']);
            foreach ($ledger->grossByCurrency($from, $to, $basis) as $row) {
                self::csvRow($out, ['', $row->currency, $row->bookings, self::amount($row->gross), self::amount($row->paid)]);
            }

            self::csvRow($out, []);
            self::csvRow($out, ['Provider payables', 'provider_id', 'provider', 'currency', 'bookings', 'package_components', 'payable', 'paid_in']);
            foreach ($ledger->payablesByProvider($from, $to, $basis) as $row) {
                self::csvRow($out, ['', $row->provider_id, $row->company_name ?? 'Unresolved', $row->currency, $row->bookings, $row->package_components, self::amount($row->payable), self::amount($row->paid_in)]);
            }

            self::csvRow($out, []);
            self::csvRow($out, ['Agent commission', 'agent_id', 'agent', 'currency', 'bookings', 'commission', 'booked_value']);
            foreach ($ledger->agentCommission($from, $to, $basis) as $row) {
                self::csvRow($out, ['', $row->agent_id, $row->agent_name ?? 'Unresolved', $row->currency, $row->bookings, self::amount($row->commission), self::amount($row->booked)]);
            }

            self::csvRow($out, []);
            self::csvRow($out, ['Cancellations and refunds', 'currency', 'cancelled', 'cancelled_value', 'refunded', 'refunded_value']);
            foreach ($ledger->cancellationsAndRefunds($from, $to, $basis) as $row) {
                self::csvRow($out, ['', $row->currency, $row->cancelled, self::amount($row->cancelled_value), $row->refunded, self::amount($row->refunded_value)]);
            }

            fclose($out);
        }, 'revenue-and-payouts-'.$from.'-to-'.$to.'.csv', ['Content-Type' => 'text/csv']);
    }

    private static function amount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /** One CSV line, with the escape character pinned (PHP 8.4 deprecates the default). */
    private static function csvRow($handle, array $fields): void
    {
        fputcsv($handle, $fields, ',', '"', '');
    }
}
