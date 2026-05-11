<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Province;
use App\Models\District;
use App\Models\City;
use App\Models\RoomType;
use App\Models\RateType;
use App\Models\CurrencyRate;
use App\Mail\PropertyRegisteredMail;
use Illuminate\Support\Facades\Mail;

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

        $rateType = new RateType();
        $rateType->property_id = $property->id;
        $rateType->rateType = "base_rate";
        $rateType->save();

        $currencyRate = new CurrencyRate();
        $currencyRate->currency_type_id = 1;
        $currencyRate->property_owner_id = auth()->user()->id;
        $currencyRate->rate_date = date('Y-m-d');
        $currencyRate->rate = 1;
        $currencyRate->save();

        $currencyRate = new CurrencyRate();
        $currencyRate->currency_type_id = 2;
        $currencyRate->property_owner_id = auth()->user()->id;
        $currencyRate->rate_date = date('Y-m-d');
        $currencyRate->rate = 300;
        $currencyRate->save();

        $this->sendPropertyRegistrationMail($property,auth()->user());

        session()->flash('success', 'Property was created successfully');

        return redirect('/my-properties');
    }

    public function viewProperty($property_id){
        $property = Property::with('city','city.district')->where('id',$property_id)->first();
        $provinces = Province::all();
        $districts = District::all();
        $cities = City::all();
        $roomTypes = RoomType::where('property_id',$property_id)->get();

        return view('property.editProperty', compact('property','provinces','districts','cities','roomTypes'));
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

        public function sendPropertyRegistrationMail($property, $user)
        {
            $data = [
                'owner_name' => $user->name,
                'owner_email' => $user->email,
                'owner_phone' => $user->phone,
                'property_name' => $property->property_name,
                'property_location' => $property->city()->city_name,
                'created_at' => now(),
                'admin_url' => url('/admin/properties/'.$property->id),
            ];

            Mail::to(env('ADMIN_EMAIL'))
                ->send(new PropertyRegisteredMail($data));
        }
}
