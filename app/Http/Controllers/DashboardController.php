<?php

namespace App\Http\Controllers;

use App\Models\PropertyRoomType;
use App\Models\RoomType;
use App\Services\ExchangeRates;

class DashboardController extends Controller
{
    // Owner dashboard: only the logged-in owner's properties
    public function index(ExchangeRates $exchangeRates)
    {
        $missingRates = $exchangeRates->missingTodayFor(auth()->user()->id);

        $properties = auth()->user()->properties()
            ->with('city')
            ->orderBy('property_name')
            ->get();

        $propertyIds = $properties->pluck('id');
        $roomCounts = PropertyRoomType::whereIn('property_id', $propertyIds)
            ->where('is_active', true)
            ->selectRaw('property_id, SUM(total_rooms) as rooms')
            ->groupBy('property_id')
            ->pluck('rooms', 'property_id');

        $stats = [
            'properties' => $properties->count(),
            'activeProperties' => $properties->where('is_active', true)->count(),
            // Room types are owner-level
            'roomTypes' => RoomType::whereIn('property_id', $propertyIds)->where('is_active', true)->count(),
            'rooms' => (int) $roomCounts->sum(),
        ];

        return view('dashboard', compact('properties', 'stats', 'missingRates', 'roomCounts'));
    }
}
