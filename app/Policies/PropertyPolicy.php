<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

/**
 * Owners: their own properties. Staff: the properties they are assigned to;
 * editing needs the "edit property details" permission. Admins pass every
 * check (Gate::before). Unapproved owners are stopped earlier by the
 * owner.approved middleware.
 */
class PropertyPolicy
{
    // Property details page, read-only rates and inventory
    public function view(User $user, Property $property): bool
    {
        return $user->isPartner() && $user->canAccessProperty($property);
    }

    // Edit property info, amenities, photos (the "edit property details" permission)
    public function update(User $user, Property $property): bool
    {
        return $user->canEditProperties() && $user->canAccessProperty($property);
    }

    public function viewRates(User $user, Property $property): bool
    {
        return $this->view($user, $property);
    }

    public function manageRates(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }

    public function viewInventory(User $user, Property $property): bool
    {
        return $this->view($user, $property);
    }

    public function manageInventory(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }
}
