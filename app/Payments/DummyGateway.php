<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Test gateway: a page on this site where the tester chooses "approve" or
 * "decline". No money moves. Links are signed, so a payment id cannot be
 * tampered with. Blocked in production unless explicitly allowed.
 */
class DummyGateway implements PaymentGateway
{
    public function __construct()
    {
        if (app()->isProduction() && !config('bedspace.allow_dummy_gateway_in_production')) {
            throw new RuntimeException('The dummy payment gateway is disabled in production.');
        }
    }

    public function name(): string
    {
        return 'dummy';
    }

    public function checkoutUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute('payments.dummy', now()->addMinutes(30), ['payment' => $payment->id]);
    }
}
