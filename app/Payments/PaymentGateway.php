<?php

namespace App\Payments;

use App\Models\Payment;

/**
 * An online payment gateway. The guest is sent to checkoutUrl(); the
 * gateway's own controller verifies the result and calls
 * App\Services\Payments::completeOnline() or failOnline().
 *
 * Implementations: DummyGateway (test page). Sampath Bank IPG to follow;
 * selected with config('bedspace.payment_gateway').
 */
interface PaymentGateway
{
    public function name(): string;

    public function checkoutUrl(Payment $payment): string;
}
