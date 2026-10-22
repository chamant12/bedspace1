<?php

namespace App\Enums;

// How a payment was actually made (PaymentMethod is what the guest chose when booking)
enum PaymentChannel: string
{
    case OnlineCard = 'online_card';     // through the payment gateway; collected by the platform
    case Cash = 'cash';                  // at the hotel
    case CardAtHotel = 'card_at_hotel';  // hotel's own card machine
    case BankTransfer = 'bank_transfer'; // to the hotel's account

    public function label(): string
    {
        return match ($this) {
            self::OnlineCard => 'Online card payment',
            self::Cash => 'Cash',
            self::CardAtHotel => 'Card at hotel',
            self::BankTransfer => 'Bank transfer',
        };
    }

    public function collectedBy(): string
    {
        return $this === self::OnlineCard ? 'platform' : 'property';
    }

    /** @return self[] channels hotel staff can record */
    public static function offline(): array
    {
        return [self::Cash, self::CardAtHotel, self::BankTransfer];
    }
}
