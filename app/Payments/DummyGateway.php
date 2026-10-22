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
    public function name(): string
    {
        return 'dummy';
    }

    public function checkoutUrl(Payment $payment): string
    {
        self::ensureAllowed();
        return URL::temporarySignedRoute('payments.dummy', now()->addMinutes(30), ['payment' => $payment->id]);
    }

    // Checked when a test payment starts or completes, not when the class is built,
    // so offline (pay at hotel) payments keep working in production
    public static function ensureAllowed(): void
    {
        if (app()->isProduction() && !config('bedspace.allow_dummy_gateway_in_production')) {
            throw new RuntimeException('The dummy payment gateway is disabled in production.');
        }
    }
}
