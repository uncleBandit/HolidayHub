@php
    use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
    use App\Modules\Administration\Filament\Resources\ProviderResource;

    $bookings = ProviderResource::bookingsFor($getRecord())
        ->with(['bookable', 'guest.user'])
        ->latest('created_at')
        ->limit(10)
        ->get();
    $canOpen = BookingOperationsResource::canViewAny();
@endphp
@include('administration::filament.partials.booking-rows', ['bookings' => $bookings, 'canOpen' => $canOpen, 'empty' => 'No bookings yet.'])
