<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\RoomType;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'owners' => User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->count(),
            'pendingOwners' => User::where('user_type_id', User::TYPE_PROPERTY_OWNER)->whereNull('approved_at')->count(),
            'properties' => Property::count(),
            'activeProperties' => Property::where('is_active', true)->count(),
            'roomTypes' => RoomType::where('is_active', true)->count(),
            'rooms' => (int) PropertyRoomType::where('is_active', true)->sum('total_rooms'),
        ];

        $latestProperties = Property::with('city', 'propertyOwner')->latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'latestProperties'));
    }
}
