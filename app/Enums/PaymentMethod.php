<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayAtHotel = 'pay_at_hotel';
    case Online = 'online'; // card payment through the gateway (dummy until Sampath Bank IPG)

    public function label(): string
    {
        return match ($this) {
            self::PayAtHotel => 'Pay at the hotel',
            self::Online => 'Pay online now (card)',
        };
    }

    // Methods guests can choose (no online option while only the blocked test gateway exists)
    public static function available(): array
    {
        $onlineUsable = config('bedspace.payment_gateway') !== 'dummy'
            || !app()->isProduction()
            || config('bedspace.allow_dummy_gateway_in_production');

        return $onlineUsable ? [self::Online, self::PayAtHotel] : [self::PayAtHotel];
    }
}
