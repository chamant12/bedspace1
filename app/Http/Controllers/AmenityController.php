<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Amenity;
use App\Models\PropertyAmenityXref;
use App\Models\RoomAmenityXref;
use App\Models\RoomType;
use App\Models\Property;
use Illuminate\Validation\Rule;
use DB;

class AmenityController extends Controller
{
    public function getAmenities($amenity_type,$type_id){
        $this->authorizeTarget($amenity_type, $type_id);

        $property_id = $type_id;
        $roomType_id = 0;
        if($amenity_type=="roomType"){
            $property_id = 0;
            $roomType_id = $type_id;
        }

        $amenities = [];
        $allAmenities = [];
        $roomTypes = [];

        if($property_id>0){
            $amenities = DB::table('amenities')
            ->join('property_amenity_xrefs', 'amenities.id', '=', 'property_amenity_xrefs.amenity_id')
            ->where('property_amenity_xrefs.property_id', $property_id)
            ->where('property_amenity_xrefs.is_active', true)
            ->where('amenities.amenity_type', 'property') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();

            $allAmenities = DB::table('amenities')
            ->where('amenities.amenity_type', 'property')
            ->where('amenities.is_active', true)
            ->whereNotIn('amenities.id', function ($query) use ($property_id) {
                $query->select('amenity_id')
                    ->from('property_amenity_xrefs')
                    ->where('property_id', $property_id)
                    ->where('is_active', true);
            })
            ->get();

            $roomTypes = RoomType::where(['property_id'=>$property_id,'is_active'=>true])->get();
        } else {
            $amenities = DB::table('amenities')
            ->join('roomType_amenity_xrefs', 'amenities.id', '=', 'roomType_amenity_xrefs.amenity_id')
            ->where('roomType_amenity_xrefs.roomType_id', $roomType_id)
            ->where('roomType_amenity_xrefs.is_active', true)
            ->where('amenities.amenity_type', 'roomType') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();

            $allAmenities = DB::table('amenities')
            ->where('amenities.amenity_type', 'roomType')
            ->where('amenities.is_active', true)
            ->whereNotIn('amenities.id', function ($query) use ($roomType_id) {
                $query->select('amenity_id')
                    ->from('roomType_amenity_xrefs')
                    ->where('roomType_id', $roomType_id)
                    ->where('is_active', true);
            })
            ->get();

        }


        $str="";
        $str2="<option value=''>Select Amenity</option>";
        $str3="<option value=''>Select Room Type</option>";
        foreach($amenities as $amenity){
            $str .= "<li id='".$amenity->id."_".$amenity_type."'>".e($amenity->amenity)." <a href='#' class='btn btn-danger' deleteAmenity='".$amenity->id.",".$amenity_type."'>Delete</a></li>";
        }
        foreach($allAmenities as $amenity){
            $str2 .="<option value='".$amenity->id."'>".e($amenity->amenity)."</option>";
        }

        foreach($roomTypes as $roomType){
            $str3 .="<option value='".$roomType->id."'>".e($roomType->roomType)."</option>";
        }

        return response()->json([
                'status' => 'success',
                'html' => $str,
                'selectHtml' => $str2,
                'roomTypeHtml' => $str3,
            ]);
    }

    // Links are deactivated, not deleted; adding the amenity again reactivates the link
    public function removeAmenity($amenity_id,$type_id,$amenity_type){
        $this->authorizeTarget($amenity_type, $type_id);

        if($amenity_type=="roomType"){
            RoomAmenityXref::where(['amenity_id'=>$amenity_id,'roomType_id'=>$type_id])->update(['is_active'=>false]);
        } else {
            PropertyAmenityXref::where(['amenity_id'=>$amenity_id,'property_id'=>$type_id])->update(['is_active'=>false]);
        }

        return response()->json([
                'status' => 'success'
            ]);
    }

    public function addAmenity(Request $request){
        $request->validate([
            'amenity_type' => ['required', 'in:property,roomType'],
            'property_id' => ['required', 'integer'],
            'room_type_id' => ['required_if:amenity_type,roomType', 'nullable', 'integer'],
            'amenity_id' => ['required', 'integer', Rule::exists('amenities', 'id')->where('amenity_type', $request->amenity_type)->where('is_active', true)],
        ]);

        if($request->amenity_type=="roomType"){
            $this->authorizeTarget('roomType', $request->room_type_id);
            RoomAmenityXref::updateOrCreate([
                'roomType_id' => $request->room_type_id,
                'amenity_id' => $request->amenity_id,
            ], ['is_active' => true]);
        } else {
            $this->authorizeTarget('property', $request->property_id);
            PropertyAmenityXref::updateOrCreate([
                'property_id' => $request->property_id,
                'amenity_id' => $request->amenity_id,
            ], ['is_active' => true]);
        }

        session()->flash('success', 'Amenity added successfully');
        return redirect()->back();
    }

    public function amenities(){
        $properties = Property::with('city','city.district')->where(['property_owner_id'=>auth()->user()->id])->get();
        return view('amenity.amenity',compact('properties'));
    }
}
