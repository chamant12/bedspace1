<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Province;
use App\Models\District;
use App\Models\City;

class PropertyController extends Controller
{
    public function listMyProperties(){
        $properties = Property::with('city','city.district')->where(['property_owner_id'=>auth()->user()->id])->get();
        return view('property.listProperties',compact('properties'));
    }

    public function addProperty(){
        $provinces = Province::all();
        return view('property.addProperty',compact('provinces'));
    }

    public function getDistrictsByProvince($province_id){
        $districts = District::where(['province_id'=>$province_id])->get()->toArray();
        return response()->json($districts, 200);
    }

    public function getCitiesByDistrict($district_id){
        $cities = City::where(['district_id'=>$district_id])->get()->toArray();
        return response()->json($cities, 200);
    }

    public function createProperty(){
        $property = new Property();
        $property->property_name = request()->property_name;
        $property->property_owner_id = auth()->user()->id;
        $property->city_id = request()->city_id;
        $property->contact_name = request()->contact_name;
        $property->contact_number = request()->contact_number;
        $property->contact_email = request()->contact_email;
        $property->address = request()->address;
        $property->location = request()->location;
        $property->save();

        session()->flash('success', 'Property was created successfully');

        return redirect('/my-properties');
    }

    public function viewProperty($property_id){
        $property = Property::with('city','city.district')->where('id',$property_id)->first();
        $provinces = Province::all();
        $districts = District::all();
        $cities = City::all();

        return view('property.editProperty', compact('property','provinces','districts','cities'));
    }

    public function updateProperty(){
        $property = Property::where('id',request()->property_id)->first();
        $property->property_name = request()->property_name;
        $property->property_owner_id = auth()->user()->id;
        $property->city_id = request()->city_id;
        $property->contact_name = request()->contact_name;
        $property->contact_number = request()->contact_number;
        $property->contact_email = request()->contact_email;
        $property->address = request()->address;
        $property->location = request()->location;
        $property->save();
        session()->flash('success', 'Property was updated successfully');
        return redirect('/my-properties');
    }
}
