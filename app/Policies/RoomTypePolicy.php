<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

// Room types are owner-level: usable by the owner's whole business
class RoomTypePolicy
{
    public function view(User $user, RoomType $roomType): bool
    {
        return $user->isPartner() && $this->sameOwner($user, $roomType);
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->canEditProperties() && $this->sameOwner($user, $roomType);
    }

    private function sameOwner(User $user, RoomType $roomType): bool
    {
        return $user->ownerId() !== null && (int) $roomType->property?->property_owner_id === $user->ownerId();
    }
}
