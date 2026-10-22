<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    // Property owner, or the guest whose account made the booking (admins pass all checks)
    public function view(User $user, Reservation $reservation): bool
    {
        return $this->ownsProperty($user, $reservation)
            || ($reservation->user_id && (int) $reservation->user_id === (int) $user->id);
    }

    // Status changes, check-in. Extend here when staff roles are added.
    public function manage(User $user, Reservation $reservation): bool
    {
        return $this->ownsProperty($user, $reservation);
    }

    private function ownsProperty(User $user, Reservation $reservation): bool
    {
        return (int) $reservation->property->property_owner_id === (int) $user->id;
    }
}
