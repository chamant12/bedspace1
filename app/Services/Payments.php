<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\ReservationStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Payments against reservations (FR-10).
 *
 * - Online: startOnline() creates a pending payment and returns the
 *   gateway URL; the gateway result calls completeOnline() / failOnline().
 *   Online money is collected by the platform.
 * - At the hotel: recordOffline() records cash / card / transfer taken
 *   by the hotel (partial payments allowed).
 *
 * On completion every payment stores the owner's exchange-rate snapshot
 * (LKR amount) and the platform commission at the reservation's rate.
 * Completing the same payment twice, or re-submitting the same form
 * (idempotency key), has no further effect.
 */
class Payments
{
    public function __construct(
        private PaymentGateway $gateway,
        private ExchangeRates $exchangeRates,
        private Reservations $reservations,
    ) {
    }

    // Starts paying the outstanding amount online; returns the URL to send the guest to
    public function startOnline(Reservation $reservation): string
    {
        $payment = DB::transaction(function () use ($reservation) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->status !== ReservationStatus::Pending) {
                throw ValidationException::withMessages(['payment' => 'This booking cannot be paid online.']);
            }
            if ($reservation->expires_at && $reservation->expires_at->isPast()) {
                throw ValidationException::withMessages(['payment' => 'This booking has expired. Please book again.']);
            }
            if ($reservation->outstanding() <= 0) {
                throw ValidationException::withMessages(['payment' => 'This booking is already paid.']);
            }

            $payment = new Payment();
            $payment->reservation_id = $reservation->id;
            $payment->method = PaymentChannel::OnlineCard;
            $payment->collected_by = PaymentChannel::OnlineCard->collectedBy();
            $payment->status = Payment::PENDING;
            $payment->idempotency_key = (string) Str::uuid();
            $payment->currency_type_id = $reservation->currency_type_id;
            $payment->amount = $reservation->outstanding();
            $payment->commission_rate = $reservation->commission_rate;
            $payment->gateway = $this->gateway->name();
            $payment->save();

            return $payment;
        });

        return $this->gateway->checkoutUrl($payment);
    }

    public function completeOnline(Payment $payment, string $gatewayReference): Payment
    {
        $confirmed = null;

        $payment = DB::transaction(function () use ($payment, $gatewayReference, &$confirmed) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === Payment::COMPLETED) {
                return $payment; // already processed (e.g. gateway retried the callback)
            }
            if ($payment->status !== Payment::PENDING) {
                throw ValidationException::withMessages(['payment' => 'This payment can no longer be completed.']);
            }

            $reservation = Reservation::with('property')->lockForUpdate()->findOrFail($payment->reservation_id);

            $payment->gateway_reference = $gatewayReference;
            $this->markCompleted($payment, $reservation, null);

            if ($reservation->status === ReservationStatus::Pending) {
                $confirmed = $this->reservations->transition($reservation, ReservationStatus::Confirmed, null, 'Online payment received ('.$gatewayReference.')');
            } else {
                // The hold expired before the gateway answered: the money must be refunded by hand
                Log::warning("Payment {$payment->id} completed for reservation {$reservation->reference} in status {$reservation->status->value}; refund required.");
                $payment->notes = 'Received after the booking was '.$reservation->status->label().'; refund required';
                $payment->save();
            }

            return $payment;
        });

        if ($confirmed) {
            $this->reservations->sendInvoice($confirmed);
        }

        return $payment;
    }

    public function failOnline(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === Payment::PENDING) {
                $payment->status = Payment::FAILED;
                $payment->notes = $reason;
                $payment->save();
            }
            return $payment;
        });
    }

    /**
     * Hotel staff record a payment taken at the property.
     * $idempotencyKey comes from the form, so a double-submitted form records once.
     */
    public function recordOffline(Reservation $reservation, float $amount, PaymentChannel $channel, ?string $reference,
                                  ?string $notes, string $idempotencyKey, User $by): Payment
    {
        if ($channel === PaymentChannel::OnlineCard) {
            throw ValidationException::withMessages(['method' => 'Online payments are recorded by the payment gateway.']);
        }

        return DB::transaction(function () use ($reservation, $amount, $channel, $reference, $notes, $idempotencyKey, $by) {
            $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $reservation = Reservation::with('property')->lockForUpdate()->findOrFail($reservation->id);

            if (in_array($reservation->status, [ReservationStatus::Cancelled, ReservationStatus::Expired], true)) {
                throw ValidationException::withMessages(['amount' => 'Payments cannot be recorded for a '.$reservation->status->label().' booking.']);
            }
            $amount = round($amount, 2);
            if ($amount <= 0 || $amount > $reservation->outstanding()) {
                throw ValidationException::withMessages(['amount' => 'The amount must be between 0.01 and the outstanding '.$reservation->money($reservation->outstanding()).'.']);
            }

            $payment = new Payment();
            $payment->reservation_id = $reservation->id;
            $payment->method = $channel;
            $payment->collected_by = $channel->collectedBy();
            $payment->idempotency_key = $idempotencyKey;
            $payment->currency_type_id = $reservation->currency_type_id;
            $payment->amount = $amount;
            $payment->commission_rate = $reservation->commission_rate;
            $payment->reference = $reference;
            $payment->notes = $notes;
            $this->markCompleted($payment, $reservation, $by);

            return $payment;
        });
    }

    // Completes a payment: exchange-rate snapshot, commission, reservation totals
    private function markCompleted(Payment $payment, Reservation $reservation, ?User $by): void
    {
        $snapshot = $this->exchangeRates->snapshot((int) $reservation->property->property_owner_id, $reservation->currency, (float) $payment->amount);

        $payment->status = Payment::COMPLETED;
        $payment->exchange_rate = $snapshot['exchange_rate'];
        $payment->amount_lkr = $snapshot['amount_lkr'];
        $payment->commission_amount = round((float) $payment->amount * (float) $payment->commission_rate / 100, 2);
        $payment->commission_lkr = round((float) $snapshot['amount_lkr'] * (float) $payment->commission_rate / 100, 2);
        $payment->paid_at = now();
        $payment->recorded_by_user_id = $by?->id;
        $payment->save();

        $paid = (float) Payment::where(['reservation_id' => $reservation->id, 'status' => Payment::COMPLETED])->sum('amount');
        $reservation->amount_paid = $paid;
        $reservation->payment_status = $paid <= 0 ? 'unpaid' : ($paid + 0.005 >= (float) $reservation->total ? 'paid' : 'partially_paid');
        $reservation->save();
    }
}
