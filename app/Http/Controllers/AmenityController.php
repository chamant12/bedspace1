<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Amenity;
use App\Model\PropertyAmenityXref;
use App\Model\RoomAmenityXref;
use App\Model\RoomType;
use App\Model\Property;
use DB;

class AmenityController extends Controller
{
    public function getAmenities($amenity_type,$type_id){
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
            ->join('amenity_property_xref', 'amenities.id', '=', 'amenity_property_xref.amenity_id')
            ->where('amenity_property_xref.type_id', $property_id)
            ->where('amenities.amenity_type', 'property') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();

            $allAmenities = DB::table('amenities')
            ->join('amenity_property_xref', 'amenities.id', '=', 'amenity_property_xref.amenity_id')
            ->notWhere('amenity_property_xref.type_id', $property_id)
            ->where('amenities.amenity_type', 'property') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();

            $roomTypes = RoomType::where('property_id',$property_id)->get();
        } else {
            $amenities = DB::table('amenities')
            ->join('roomType_amenity_xrefs', 'amenities.id', '=', 'roomType_amenity_xrefs.amenity_id')
            ->where('roomType_amenity_xrefs.amenity_id', $amenity_id)
            ->where('amenities.amenity_type', 'roomType') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();

            $allAmenities = DB::table('amenities')
            ->join('roomType_amenity_xrefs', 'amenities.id', '=', 'roomType_amenity_xrefs.amenity_id')
            ->notWhere('roomType_amenity_xrefs.amenity_id', $amenity_id)
            ->where('amenities.amenity_type', 'roomType') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();
        }
        
        $str="";
        $str2="<option value=''>Select Amenity</option>";
        $str3="<option value=''>Select Room Type</option>";
        foreach($amenities as $amenity){
            $str .= "<li id='".$amenity->id."_".$amenity_type."'>".$amenity->amenity." <a href='#' class='btn btn-danger' onclick='deleteAmenity(".$amenity->id.",".$amenity_type.")'>Delete</a></li>";
        }
        foreach(allAmenities as $amenity){
            $str2 .="<option value='".$amenity->id.'_'.$amenity_type."'>".$amenity->amenity."</option>";
        }

        foreach(roomTypes as $roomType){
            $str3 .="<option value='".$roomType->id."'>".$roomType->roomType."</option>";
        }

        return response()->json([
                'status' => 'success',
                'html' => $str,
                'selectHtml' => $str2,
                'roomTypeHtml' => $str3,
            ]);
    }

    public function deleteAmenity($amenity_id,$amenity_type){
        if($amenity_type=="roomType"){
            RoomAmenityXref::where('id',$amenity_id)->delete();
        } else {
            PropertyAmenityXref::where('id',$amenity_id)->delete();
        }

        return response()->json([
                'status' => 'success'
            ]);
    }

    public function addAmenity(){
        if(request()->amenity_type=="roomType"){
            $roomAmenityXref = new RoomAmenityXref();
            $roomAmenityXref->roomType_id = request()->room_type_id;
            $roomAmenityXref->amenity_id = request()->amenity_id;
            $roomAmenityXref->save();
        } else {
            $propertyAmenityXref = new PropertyAmenityXref();
            $propertyAmenityXref->property_id = request()->property_id;
            $propertyAmenityXref->amenity_id = request()->amenity_id;
            $propertyAmenityXref->save();
        }

        session()->flash('success', 'Amenity added successfully');
        return redirect()->back();
    }

    public function amenities(){
        $properties = Property::with('city','city.district')->where(['property_owner_id'=>auth()->user()->id])->get();
        return view('amenity.amenity',compact('properties'));
    }
}
