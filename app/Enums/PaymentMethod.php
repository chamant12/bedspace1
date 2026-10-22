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

    // Methods guests can choose
    public static function available(): array
    {
        return [self::Online, self::PayAtHotel];
    }
}
