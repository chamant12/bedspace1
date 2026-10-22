<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Booking confirmation / invoice; $isCopy = the owner's or contact person's copy
class ReservationInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public bool $isCopy = false)
    {
    }

    public function build()
    {
        $subject = $this->isCopy
            ? "New booking {$this->reservation->reference} - {$this->reservation->property->property_name}"
            : "Your booking {$this->reservation->reference} at {$this->reservation->property->property_name}";

        return $this->subject($subject)->view('email.reservationInvoice');
    }
}
