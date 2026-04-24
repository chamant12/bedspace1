<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\City;
use App\Models\RoomType;
use App\Models\Room;

class RoomTypeController extends Controller
{
    public function addRoomType(){
        $properties = Property::where('property_owner_id',auth()->user()->id)->get();
        $city_ids = [];
        foreach($properties as $property){
            $city_ids[] = $property->city_id;
        }
        $cities = City::whereIn('id',$city_ids)->get();
        return view('room.addRoomType', compact('cities'));
    }

    public function getPropertiesByCity($city_id,$property_owner_id){
        $properties = Property::where(['city_id'=>$city_id,'property_owner_id'=>$property_owner_id])->get();
        return response()->json($properties, 200);
    }

    public function createRoomType(){
        $roomType = new RoomType();
        $roomType->property_id = request()->property_id;
        $roomType->roomType = request()->roomType;
        $roomType->max_occupancy = request()->max_occupancy;
        $roomType->save();

        $noOfRooms = request()->noOfRooms;
        for($i=1;$i<=$noOfRooms;$i++){
            $room = new Room();
            $room->roomType_id = $roomType->id;
            $room->save();
        }

        session()->flash('success', 'Room Type was created successfully');

        return redirect('/my-properties');
    }

    public function deleteRoomType($roomType_id){
        Room::where('roomType_id',$roomType_id)->delete();
        RoomType::where('id',$roomType_id)->delete();
        session()->flash('success', 'Room Type was deleted successfully');

        return redirect()->back();

    }

    public function viewRoomType($roomType_id){
        $roomType = RoomType::where('id',$roomType_id)->first();
        $thisProperty = Property::where('id',$roomType->property_id)->first();
        $properties = Property::where(['property_owner_id'=>auth()->user()->id])->get();
        $city_ids = [];
        $city_id = $thisProperty->city_id;
        foreach($properties as $property){
            $city_ids[] = $property->city_id;
        }
        $cities = City::whereIn('id',$city_ids)->get();
        $noOfRooms = Room::where('roomType_id',$roomType_id)->count();
        return view('room.editRoomType', compact('cities','properties','city_id','roomType','noOfRooms'));
    }

    public function updateRoomType(){
        $roomType = RoomType::where('id',request()->roomType_id)->first();
        $roomType->roomType = request()->roomType;
        $roomType->max_occupancy = request()->max_occupancy;
        $roomType->save();

        session()->flash('success', 'Room Type was updated successfully');

        return redirect('/my-properties');
    }
}
