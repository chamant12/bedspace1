<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::with('city', 'city.district', 'propertyOwner')
            ->withCount('roomTypes')
            ->orderBy('property_name')
            ->get();

        return view('admin.properties', compact('properties'));
    }

    public function toggleActive($property_id)
    {
        $property = Property::findOrFail($property_id);
        $property->is_active = !$property->is_active;
        $property->save();

        session()->flash('success', $property->property_name.' was '.($property->is_active ? 'activated' : 'deactivated'));
        return redirect()->back();
    }
}
