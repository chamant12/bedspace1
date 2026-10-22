<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use App\Models\Province;
use App\Models\District;
use App\Models\City;
use App\Models\RoomType;
use App\Models\RateType;
use App\Models\CurrencyRate;
use App\Models\CurrencyType;
use App\Services\ExchangeRates;
use App\Support\BusinessDate;
use App\Mail\PropertyRegisteredMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class PropertyController extends Controller
{
    public function listMyProperties(){
        $properties = Property::with('city','city.district')->where(['property_owner_id'=>auth()->user()->id])->get();
        return view('property.listProperties',compact('properties'));
    }

    public function addProperty(ExchangeRates $exchangeRates){
        $provinces = Province::all();
        $foreignCurrencies = CurrencyType::active()->where('is_base', false)->orderBy('id')->get();
        // Pre-fill with the owner's current rate (or the latest on the platform)
        $currentRates = $foreignCurrencies->mapWithKeys(fn ($currency) => [
            $currency->id => $exchangeRates->latestRate(auth()->user()->id, $currency),
        ]);
        return view('property.addProperty',compact('provinces','foreignCurrencies','currentRates'));
    }

    public function getDistrictsByProvince($province_id){
        $districts = District::where(['province_id'=>$province_id])->get()->toArray();
        return response()->json($districts, 200);
    }

    public function getCitiesByDistrict($district_id){
        $cities = City::where(['district_id'=>$district_id])->get()->toArray();
        return response()->json($cities, 200);
    }

    public function createProperty(PropertyRequest $request){
        $property = DB::transaction(function () use ($request) {
            $property = new Property();
            $property->fill($request->propertyData());
            $property->property_owner_id = auth()->user()->id;
            $property->save();

            $property->contacts()->create($request->contactData() + ['is_primary' => true]);

            $rateType = new RateType();
            $rateType->property_id = $property->id;
            $rateType->rateType = "base_rate";
            $rateType->save();

            // Exchange rates are per owner; this guarantees one exists from day one
            foreach ($request->exchangeRates() as $currencyId => $rate) {
                $currencyRate = new CurrencyRate();
                $currencyRate->currency_type_id = $currencyId;
                $currencyRate->property_owner_id = auth()->user()->id;
                $currencyRate->rate_date = BusinessDate::today();
                $currencyRate->rate = $rate;
                $currencyRate->save();
            }

            return $property;
        });

        $this->sendPropertyRegistrationMail($property,auth()->user());

        session()->flash('success', 'Property was created successfully');

        return redirect('/my-properties');
    }

    public function viewProperty($property_id){
        $property = Property::with('city','city.district','primaryContact')->findOrFail($property_id);
        Gate::authorize('view', $property);
        $provinces = Province::all();
        $districts = District::all();
        $cities = City::all();
        $roomTypes = RoomType::where('property_id',$property_id)->get();

        return view('property.editProperty', compact('property','provinces','districts','cities','roomTypes'));
    }

    public function updateProperty(PropertyRequest $request){
        $property = Property::findOrFail($request->property_id);
        Gate::authorize('update', $property);

        DB::transaction(function () use ($request, $property) {
            $property->fill($request->propertyData());
            $property->save();

            $property->contacts()->updateOrCreate(['is_primary' => true], $request->contactData());
        });
        session()->flash('success', 'Property was updated successfully');
        return redirect('/my-properties');
    }

    // Properties are deactivated (hidden from guests), never deleted
    public function togglePropertyActive($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('update', $property);

        $property->is_active = !$property->is_active;
        $property->save();

        session()->flash('success', $property->property_name.' was '.($property->is_active ? 'activated' : 'deactivated'));
        return redirect()->back();
    }

        public function sendPropertyRegistrationMail($property, $user)
        {
            $data = [
                'owner_name' => $user->name,
                'owner_email' => $user->email,
                'owner_phone' => $user->phone,
                'property_name' => $property->property_name,
                'property_location' => $property->city?->city_name,
                'created_at' => now(),
                'admin_url' => url('/admin/properties/'.$property->id),
            ];

            // The property is already saved; a mail failure must not turn into an error page
            try {
                Mail::to(config('mail.admin_address'))
                    ->send(new PropertyRegisteredMail($data));
            } catch (\Throwable $e) {
                report($e);
            }
        }
}
