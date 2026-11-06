@php
    use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
    use App\Modules\Booking\Domain\Models\Booking;

    $user = $getRecord();
    $query = Booking::query()->whereHas('guest', fn ($guest) => $guest->where('user_id', $user->id));
    $total = (clone $query)->count();
    $bookings = $query->with(['bookable', 'guest.user'])->latest('created_at')->limit(10)->get();
@endphp
@include('administration::filament.partials.booking-rows', [
    'bookings' => $bookings,
    'canOpen' => BookingOperationsResource::canViewAny(),
    'empty' => 'This account has no bookings.',
])
@if ($total > $bookings->count())
    <p class="text-xs text-gray-500" style="margin-top:.4rem">{{ $total }} bookings in total.</p>
@endif
