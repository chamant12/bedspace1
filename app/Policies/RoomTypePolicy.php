<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function view(User $user, RoomType $roomType): bool
    {
        return $this->owns($user, $roomType);
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $this->owns($user, $roomType);
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $this->owns($user, $roomType);
    }

    private function owns(User $user, RoomType $roomType): bool
    {
        return (int) $roomType->property?->property_owner_id === (int) $user->id;
    }
}
