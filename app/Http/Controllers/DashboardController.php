<?php

namespace App\Http\Controllers;

use App\Models\Room;

class DashboardController extends Controller
{
    // Owner dashboard: only the logged-in owner's properties
    public function index()
    {
        $properties = auth()->user()->properties()
            ->with('city')
            ->withCount('roomTypes')
            ->orderBy('property_name')
            ->get();

        $propertyIds = $properties->pluck('id');

        $stats = [
            'properties' => $properties->count(),
            'activeProperties' => $properties->where('is_active', true)->count(),
            'roomTypes' => $properties->sum('room_types_count'),
            'rooms' => Room::whereIn('roomType_id', function ($query) use ($propertyIds) {
                $query->select('id')->from('roomtypes')->whereIn('property_id', $propertyIds);
            })->count(),
        ];

        return view('dashboard', compact('properties', 'stats'));
    }
}
