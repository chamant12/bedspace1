<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        if($property_id>0){
            $amenities = DB::table('amenities')
            ->join('amenity_property_xref', 'amenities.id', '=', 'amenity_property_xref.amenity_id')
            ->where('amenity_property_xref.type_id', $property_id)
            ->where('amenities.amenity_type', 'property') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();
        } else {
            $amenities = DB::table('amenities')
            ->join('roomType_amenity_xrefs', 'amenities.id', '=', 'roomType_amenity_xrefs.amenity_id')
            ->where('roomType_amenity_xrefs.amenity_id', $amenity_id)
            ->where('amenities.amenity_type', 'roomType') // Based on your 'amenity_type' filter
            ->select('amenities.*')
            ->get();
        }
        
        $str="";
        foreach($amenities as $amenity){
            $str .= "<li>".$amenity->amenity." <a href='#' class='btn btn-danger' onclick='deleteAmenity(".$amenity->id.",".$roomType_id.",".$property_id.")'>Delete</a></li>";
        }

        return response()->json([
                'status' => 'success',
                'html' => $str,
            ]);
    }
}
