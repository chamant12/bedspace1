<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Services\PropertyStatus;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::with('city', 'city.district', 'propertyOwner')
            ->orderBy('property_name')
            ->get();

        $roomCounts = PropertyRoomType::where('is_active', true)
            ->selectRaw('property_id, SUM(total_rooms) as rooms')
            ->groupBy('property_id')
            ->pluck('rooms', 'property_id');

        return view('admin.properties', compact('properties', 'roomCounts'));
    }

    public function toggleActive($property_id, PropertyStatus $status)
    {
        $property = Property::with('propertyOwner')->findOrFail($property_id);
        $status->setActiveByAdmin($property, !$property->is_active);

        session()->flash('success', $property->property_name.' was '.($property->is_active ? 'activated' : 'deactivated'));
        return redirect()->back();
    }
}
