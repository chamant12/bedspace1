<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyRoomType;
use Illuminate\Http\Request;

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

    // Platform commission for this property's future bookings; empty = platform default
    public function updateCommission(Request $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $request->validate(['commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100']]);

        $property->commission_rate = $request->filled('commission_rate') ? $request->commission_rate : null;
        $property->save();

        session()->flash('success', 'Commission for '.$property->property_name.' is now '.number_format($property->effectiveCommissionRate(), 2).'% (applies to new bookings)');
        return redirect()->back();
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
