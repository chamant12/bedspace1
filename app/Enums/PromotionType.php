<?php

namespace App\Enums;

enum PromotionType: string
{
    case EarlyBird = 'early_bird';
    case LastMinute = 'last_minute';
    case LongStay = 'long_stay';
    case Seasonal = 'seasonal';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::EarlyBird => 'Early Bird',
            self::LastMinute => 'Last Minute',
            self::LongStay => 'Long Stay',
            self::Seasonal => 'Seasonal',
            self::Custom => 'Custom',
        };
    }
}
