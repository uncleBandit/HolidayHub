<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use App\Services\BookingManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Livewire\Attributes\On;

class BookingForm extends Component
{
    /** @var string Supported bookable type (hotel, villa, tour, flight) */
    public string $bookableType = 'hotel';

    public string $confirmationMessage = '';

    /** @var int|null ID of the bookable (hotel_id, villa_id, etc.) */
    public ?int $bookableId = null;

    /** @var int|null Room ID (for hotels/villas) */
    public ?int $roomId = null;

    /** @var string|null Dates */
    public ?string $checkIn = null;
    public ?string $checkOut = null;

    /** @var int Number of guests */
    public int $guests = 1;

    /** @var float|null Live preview of price */
    public ?float $pricePreview = null;

    /** @var string|null Success confirmation */

    protected array $rules = [
        'bookableType' => 'required|string|in:hotel,villa,tour,flight',
        'bookableId'   => 'required|integer|min:1',
        'roomId'       => 'nullable|integer|min:1',
        'checkIn'      => 'required|date|after_or_equal:today',
        'checkOut'     => 'required|date|after:checkIn',
        'guests'       => 'required|integer|min:1|max:20',
    ];

    /**
     * Auto preview when dates or bookable change.
     */
    public function updated($field)
    {
        try {
            $this->validateOnly($field);

            if ($this->checkIn && $this->checkOut && $this->bookableId) {
                $this->pricePreview = app(BookingManager::class)
                    ->previewPrice(
                        $this->bookableType,
                        $this->bookableId,
                        $this->roomId,
                        Carbon::parse($this->checkIn),
                        Carbon::parse($this->checkOut),
                        $this->guests
                    );
            }
        } catch (ValidationException $e) {
            // Livewire will show inline validation messages
        }
    }

    #[On('staySelected')]
    public function setStay($checkin, $checkout) {
        $this->checkIn = $checkin;
        $this->checkOut = $checkout;
    }

    /**
     * Submit booking request.
     */
    public function submit(BookingManager $bookingManager)
    {
        $this->resetErrorBag();
        //$this->confirmationMessage = null;

        $this->validate();

        try {
            $booking = $bookingManager->create([
                'bookable_type' => $this->bookableType,
                'bookable_id'   => $this->bookableId,
                'room_id'       => $this->roomId,
                'check_in'      => $this->checkIn,
                'check_out'     => $this->checkOut,
                'guests'        => $this->guests,
            ],
                 Auth::id(),
            );

            $this->confirmationMessage = __("Booking #:id confirmed!", ['id' => $booking->id]);

            $this->reset(['roomId','checkIn','checkOut','guests','pricePreview']);
            $this->guests = 1; // restore sensible default
        } catch (\Throwable $e) {
            Log::error("Booking failed", [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->addError('general', __("Unable to complete booking. Please try again or contact support."));
        }
    }

    public function previewPrice(string $type, int $id, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
    // Example: delegate to specific pricing engine
    $days = $checkIn->diffInDays($checkOut);
    return match ($type) {
        'hotel', 'villa' => $this->calculateRoomPrice($id, $roomId, $days, $guests),
        'tour'           => $this->calculateTourPrice($id, $guests),
        'flight'         => $this->calculateFlightPrice($id, $guests),
        default          => throw new \InvalidArgumentException("Unsupported bookable type"),
    };
    }


    protected $listeners = ['bookingInitiated' => 'fillFromCalendar'];

    public function fillFromCalendar(array $data): void
    {
    // Map calendar data into BookingForm fields
    $this->bookableType = strtolower(class_basename($data['bookable_type']));
    $this->bookableId   = $data['bookable_id'];
    $this->checkIn      = $data['checkin'];
    $this->checkOut     = $data['checkout'];
    $this->pricePreview = $data['total'];

    // Optionally auto-set guests
    if (!$this->guests) {
        $this->guests = 1;
    }
    }



    public function render()
    {
        return view('livewire.forms.booking-form');
    }
}
