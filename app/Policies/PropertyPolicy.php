<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function view(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    private function owns(User $user, Property $property): bool
    {
        return (int) $property->property_owner_id === (int) $user->id;
    }
}
