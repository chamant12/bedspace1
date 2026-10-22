<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PayAtHotel = 'pay_at_hotel';
    case Online = 'online'; // Sampath Bank IPG, after MVP phase 1

    public function label(): string
    {
        return match ($this) {
            self::PayAtHotel => 'Pay at the hotel',
            self::Online => 'Pay online',
        };
    }

    // Methods guests can choose today
    public static function available(): array
    {
        return [self::PayAtHotel];
    }
}
