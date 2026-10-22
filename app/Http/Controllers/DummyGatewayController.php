<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Payments\DummyGateway;
use App\Services\Payments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Pages of the dummy payment gateway (App\Payments\DummyGateway).
 * Both routes are signed; nothing here moves real money.
 */
class DummyGatewayController extends Controller
{
    public function __construct(private Payments $payments)
    {
    }

    public function show(Payment $payment){
        $payment->load('reservation.property', 'currency');
        abort_unless($payment->gateway === 'dummy', 404);

        $resultUrl = URL::temporarySignedRoute('payments.dummy.result', now()->addMinutes(30), ['payment' => $payment->id]);

        return view('payments.dummy', compact('payment', 'resultUrl'));
    }

    public function result(Request $request, Payment $payment){
        abort_unless($payment->gateway === 'dummy', 404);
        DummyGateway::ensureAllowed();
        $request->validate(['result' => ['required', 'in:approve,decline']]);

        if ($payment->status === Payment::PENDING) {
            $request->result === 'approve'
                ? $this->payments->completeOnline($payment, 'DUMMY-'.strtoupper(Str::random(10)))
                : $this->payments->failOnline($payment, 'Declined on the test gateway');
        }

        $reservation = $payment->reservation()->first();
        $bookingUrl = URL::temporarySignedRoute('booking.show', now()->addDays(30), ['reference' => $reservation->reference]);

        return $payment->fresh()->status === Payment::COMPLETED
            ? redirect($bookingUrl)->with('success', 'Payment received. Your booking is confirmed and a confirmation has been emailed to '.$reservation->guest_email.'.')
            : redirect($bookingUrl)->with('error', 'The payment was declined. You can try again before the booking expires.');
    }
}
