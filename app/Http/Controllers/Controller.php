<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    /**
     * Photos and amenities attach to either a property or a room type,
     * identified by a type string ("property" | "roomType") and an id.
     * Aborts with 404/403 unless the current user owns the target.
     */
    protected function authorizeTarget(string $type, $id): Property|RoomType
    {
        $model = $type === 'roomType'
            ? RoomType::findOrFail($id)
            : Property::findOrFail($id);

        Gate::authorize('update', $model);

        return $model;
    }

    /**
     * Room types are owner-level: any of the property owner's room types may
     * be used at any of their properties. Authorizes $ability on the property
     * and checks the room type belongs to the same owner. [Property, RoomType]
     */
    protected function resolvePropertyRoomType($property_id, $roomType_id, string $ability): array
    {
        $property = Property::findOrFail($property_id);
        Gate::authorize($ability, $property);

        $roomType = RoomType::with('property')->findOrFail($roomType_id);
        abort_unless((int) $roomType->property?->property_owner_id === (int) $property->property_owner_id, 404);

        return [$property, $roomType];
    }

    // Active room types of the property's owner (usable at any of their properties)
    protected function ownerRoomTypes(Property $property)
    {
        return RoomType::where('is_active', true)
            ->whereIn('property_id', Property::where('property_owner_id', $property->property_owner_id)->select('id'))
            ->orderBy('roomType')
            ->get();
    }
}
