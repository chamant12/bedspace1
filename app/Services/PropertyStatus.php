<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enabling and disabling properties and owner accounts.
 *
 * - Disabling an owner immediately disables all their active properties;
 *   re-enabling the owner brings back only those properties.
 * - A property disabled by the admin can only be re-enabled by the admin.
 * - Nothing is deleted; bookings already made are kept.
 */
class PropertyStatus
{
    const BY_OWNER = 'owner';
    const BY_ADMIN = 'admin';
    const WITH_OWNER_ACCOUNT = 'owner_account_disabled';

    public function setActiveByAdmin(Property $property, bool $active): void
    {
        if ($active && !$property->propertyOwner?->status) {
            throw ValidationException::withMessages(['property' => 'The owner\'s account is disabled. Enable the owner to bring their properties back.']);
        }
        $this->set($property, $active, self::BY_ADMIN);
    }

    public function setActiveByOwner(Property $property, bool $active): void
    {
        if ($active && in_array($property->deactivated_reason, [self::BY_ADMIN, self::WITH_OWNER_ACCOUNT], true)) {
            throw ValidationException::withMessages(['property' => 'This property was disabled by EpickTours. Please contact us to enable it again.']);
        }
        $this->set($property, $active, self::BY_OWNER);
    }

    // Disables the owner (no sign-in for them or their staff) and every active property
    public function disableOwner(User $owner): int
    {
        return DB::transaction(function () use ($owner) {
            $owner->status = 0;
            $owner->save();

            $properties = Property::where(['property_owner_id' => $owner->id, 'is_active' => true])->get();
            foreach ($properties as $property) {
                $this->set($property, false, self::WITH_OWNER_ACCOUNT);
            }
            return $properties->count();
        });
    }

    // Enables the owner and the properties that were disabled together with the account
    public function enableOwner(User $owner): int
    {
        return DB::transaction(function () use ($owner) {
            $owner->status = 1;
            $owner->save();

            $properties = Property::where(['property_owner_id' => $owner->id, 'is_active' => false, 'deactivated_reason' => self::WITH_OWNER_ACCOUNT])->get();
            foreach ($properties as $property) {
                $this->set($property, true, self::WITH_OWNER_ACCOUNT);
            }
            return $properties->count();
        });
    }

    private function set(Property $property, bool $active, string $reason): void
    {
        $property->is_active = $active;
        $property->deactivated_reason = $active ? null : $reason;
        $property->deactivated_at = $active ? null : now();
        $property->save();
    }
}
