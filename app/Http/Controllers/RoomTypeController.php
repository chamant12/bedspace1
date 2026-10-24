<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RoomTypeRequest;
use App\Models\Property;
use App\Models\City;
use App\Models\RoomType;
use App\Models\PropertyRoomType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoomTypeController extends Controller
{
    public function addRoomType(){
        $properties = Property::whereIn('id', auth()->user()->accessiblePropertyIds())->get();
        $city_ids = [];
        foreach($properties as $property){
            $city_ids[] = $property->city_id;
        }
        $cities = City::whereIn('id',$city_ids)->get();
        return view('room.addRoomType', compact('cities'));
    }

    public function getPropertiesByCity($city_id){
        $properties = Property::where('city_id', $city_id)->whereIn('id', auth()->user()->accessiblePropertyIds())->get();
        return response()->json($properties, 200);
    }

    public function createRoomType(RoomTypeRequest $request){
        Gate::authorize('update', Property::findOrFail($request->property_id));

        // The room type is owner-level; its room count is set for the chosen property
        // (other properties get theirs on the Inventory page). Room numbers are kept separately.
        DB::transaction(function () use ($request) {
            $roomType = new RoomType();
            $roomType->property_id = $request->property_id;
            $roomType->roomType = $request->roomType;
            $roomType->max_occupancy = $request->max_occupancy;
            $roomType->save();

            PropertyRoomType::create([
                'property_id' => $request->property_id,
                'roomType_id' => $roomType->id,
                'total_rooms' => $request->noOfRooms,
            ]);
        });

        session()->flash('success', 'Room Type was created successfully');

        return redirect('/my-properties');
    }

    // Room types are deactivated (no longer bookable), never deleted
    public function toggleRoomTypeActive($roomType_id){
        $roomType = RoomType::findOrFail($roomType_id);
        Gate::authorize('update', $roomType);

        $roomType->is_active = !$roomType->is_active;
        $roomType->save();

        session()->flash('success', 'Room Type was '.($roomType->is_active ? 'activated' : 'deactivated'));

        return redirect()->back();
    }

    public function viewRoomType($roomType_id){
        $roomType = RoomType::findOrFail($roomType_id);
        Gate::authorize('view', $roomType);
        // Room counts of this room type at each of the owner's properties
        $properties = Property::whereIn('id', auth()->user()->accessiblePropertyIds())->orderBy('property_name')->get();
        $roomCounts = PropertyRoomType::where('roomType_id', $roomType->id)->pluck('total_rooms', 'property_id');
        return view('room.editRoomType', compact('roomType','properties','roomCounts'));
    }

    public function updateRoomType(RoomTypeRequest $request){
        $roomType = RoomType::findOrFail($request->roomType_id);
        Gate::authorize('update', $roomType);
        $roomType->roomType = $request->roomType;
        $roomType->max_occupancy = $request->max_occupancy;
        $roomType->save();

        session()->flash('success', 'Room Type was updated successfully');

        return redirect('/my-properties');
    }
}
