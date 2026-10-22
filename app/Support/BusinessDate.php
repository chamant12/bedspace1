<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Calendar dates in the business timezone (Sri Lanka), used for every
 * "today / past date" rule. Rates and bookings are stored as plain dates.
 */
class BusinessDate
{
    public static function today(): string
    {
        return CarbonImmutable::now(config('bedspace.timezone'))->toDateString();
    }

    public static function isPast(string $date): bool
    {
        return $date < self::today();
    }
}
