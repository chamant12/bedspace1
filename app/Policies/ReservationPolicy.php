<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    // Owner / assigned staff of the property, or the guest whose account made the booking (admins pass all checks)
    public function view(User $user, Reservation $reservation): bool
    {
        return $this->manage($user, $reservation)
            || ($reservation->user_id && (int) $reservation->user_id === (int) $user->id);
    }

    // Status changes, check-in, payments: owner and every staff member assigned to the property
    public function manage(User $user, Reservation $reservation): bool
    {
        return $user->isPartner() && $user->canAccessProperty($reservation->property);
    }
}
