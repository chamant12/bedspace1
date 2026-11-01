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
 * On completion every payment stores the platform commission at the
 * reservation's rate, in the payment's own currency (no conversion).
 * Completing the same payment twice, or re-submitting the same form
 * (idempotency key), has no further effect.
 */
class Payments
{
    public function __construct(
        private PaymentGateway $gateway,
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

    /**
     * Refunds money to the guest of a cancelled booking that has a refund due
     * (cancellation policy). $collectedBy says whose money is refunded:
     * - platform: online payments, refunded through the gateway (admin)
     * - property: payments taken at the hotel, refunded by the hotel ($channel)
     * The refund row reverses the amount and the commission, so every
     * report, the commission dues and the payouts are reduced accordingly.
     */
    public function refund(Reservation $reservation, string $collectedBy, float $amount, ?PaymentChannel $channel,
                           ?string $reference, ?string $notes, string $idempotencyKey, User $by): Payment
    {
        return DB::transaction(function () use ($reservation, $collectedBy, $amount, $channel, $reference, $notes, $idempotencyKey, $by) {
            $existing = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $reservation = Reservation::with('property')->lockForUpdate()->findOrFail($reservation->id);
            if ($reservation->refund_status !== 'due') {
                throw ValidationException::withMessages(['amount' => 'This booking has no refund due.']);
            }

            // Money of this collector still held (payments less earlier refunds)
            $rows = Payment::where(['reservation_id' => $reservation->id, 'status' => Payment::COMPLETED, 'collected_by' => $collectedBy])->get();
            $held = round((float) $rows->sum('amount'), 2);
            $maximum = min($held, $reservation->refundRemaining());

            $amount = round($amount, 2);
            if ($amount <= 0 || $amount > $maximum) {
                throw ValidationException::withMessages(['amount' => 'The refund must be between 0.01 and '.$reservation->money($maximum).'.']);
            }

            $originals = $rows->where('kind', Payment::KIND_PAYMENT);

            $refund = new Payment();
            $refund->reservation_id = $reservation->id;
            $refund->kind = Payment::KIND_REFUND;
            $refund->idempotency_key = $idempotencyKey;
            $refund->collected_by = $collectedBy;
            $refund->currency_type_id = $reservation->currency_type_id;
            $refund->amount = -$amount;
            $refund->commission_rate = $reservation->commission_rate;
            $refund->commission_amount = -round($amount * (float) $reservation->commission_rate / 100, 2);
            $refund->reference = $reference;
            $refund->notes = $notes;
            $refund->recorded_by_user_id = $by->id;

            if ($collectedBy === 'platform') {
                $original = $originals->sortByDesc('id')->first();
                $refund->method = PaymentChannel::OnlineCard;
                $refund->gateway = $this->gateway->name();
                $refund->gateway_reference = $this->gateway->refund($original, $amount);
            } else {
                if (!$channel || $channel === PaymentChannel::OnlineCard) {
                    throw ValidationException::withMessages(['method' => 'Choose how the hotel refunded the guest.']);
                }
                $refund->method = $channel;
            }

            $refund->status = Payment::COMPLETED;
            $refund->paid_at = now();
            $refund->save();

            $reservation->amount_refunded = round((float) $reservation->amount_refunded + $amount, 2);
            $reservation->amount_paid = round((float) Payment::where(['reservation_id' => $reservation->id, 'status' => Payment::COMPLETED])->sum('amount'), 2);
            if ($reservation->refundRemaining() <= 0) {
                $reservation->refund_status = 'refunded';
            }
            $reservation->payment_status = $reservation->amount_paid <= 0 ? 'refunded' : 'partially_refunded';
            $reservation->save();

            return $refund;
        });
    }

    // Completes a payment: commission (same currency as the payment), reservation totals
    private function markCompleted(Payment $payment, Reservation $reservation, ?User $by): void
    {
        $payment->status = Payment::COMPLETED;
        $payment->commission_amount = round((float) $payment->amount * (float) $payment->commission_rate / 100, 2);
        $payment->paid_at = now();
        $payment->recorded_by_user_id = $by?->id;
        $payment->save();

        $paid = (float) Payment::where(['reservation_id' => $reservation->id, 'status' => Payment::COMPLETED])->sum('amount');
        $reservation->amount_paid = $paid;
        $reservation->payment_status = $paid <= 0 ? 'unpaid' : ($paid + 0.005 >= (float) $reservation->total ? 'paid' : 'partially_paid');
        $reservation->save();
    }
}
