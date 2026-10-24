<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Reservation;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;

/**
 * Cancellation policy: a booking cancelled N days or more before check-in
 * is refunded in full; cancellations within N days of check-in, and
 * no-shows, are not refunded. N is set per property (default
 * BEDSPACE_FREE_CANCELLATION_DAYS) and fixed on each booking when made.
 * Dates are Sri Lanka dates.
 */
class CancellationPolicy
{
    // Last day a cancellation is refunded, or null if the booking is already inside the window
    public function freeCancellationUntil(string $checkIn, int $days, ?string $bookedOn = null): ?string
    {
        $until = CarbonImmutable::parse($checkIn)->subDays($days)->toDateString();
        return $until >= ($bookedOn ?? BusinessDate::today()) ? $until : null;
    }

    public function isRefundable(Reservation $reservation, ?string $on = null): bool
    {
        return $reservation->free_cancellation_until !== null
            && ($on ?? BusinessDate::today()) <= $reservation->free_cancellation_until->toDateString();
    }

    // Policy text for a property, before booking (shown in the terms at checkout)
    public function statementForStay(Property $property, string $checkIn): string
    {
        $days = $property->effectiveCancellationDays();
        $until = $this->freeCancellationUntil($checkIn, $days);

        return $until
            ? "Free cancellation until ".CarbonImmutable::parse($until)->format('D, d M Y')." (Sri Lanka time). "
              ."Cancellations within {$days} day(s) of check-in, and no-shows, are not refunded."
            : "This booking is non-refundable: check-in is within {$days} day(s), so cancellations and no-shows are not refunded.";
    }

    // Policy text for an existing booking
    public function statementForReservation(Reservation $reservation): string
    {
        return $reservation->free_cancellation_until
            ? "Free cancellation until ".$reservation->free_cancellation_until->format('D, d M Y')." (Sri Lanka time). "
              ."Cancellations within {$reservation->cancellation_days} day(s) of check-in, and no-shows, are not refunded."
            : "Non-refundable: cancellations and no-shows are not refunded.";
    }

    // Generic statement for property pages (no dates chosen yet)
    public function summary(Property $property): string
    {
        $days = $property->effectiveCancellationDays();
        return "Free cancellation up to {$days} day(s) before check-in. Later cancellations and no-shows are not refunded.";
    }
}
