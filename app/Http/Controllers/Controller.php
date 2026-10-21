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
}
