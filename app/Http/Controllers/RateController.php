<?php

namespace App\Http\Controllers;


use App\Models\Property;
use App\Models\City;
use App\Models\RoomType;
use App\Models\BaseRate;
use App\Models\MealType;
use DateTime;
use DateInterval; // Add this
use DatePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Models\Occupancy;
use App\Models\CurrencyType;
use App\Models\RateType;
use App\Models\CurrencyRate;

class RateController extends Controller
{
    public function addBaseRate($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('update', $property);
        $roomTypes = RoomType::where('property_id',$property_id)->get();
        $currencies = CurrencyType::active()->orderBy('id')->get();
        return view('rate.addBaseRate', compact('property','roomTypes','currencies'));
    }

    public function getBaseRates($roomType_id,$currency_id,$from_date,$to_date){
        Validator::make(compact('currency_id','from_date','to_date'), [
            'currency_id' => ['required', 'integer', 'exists:currency_types,id'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after:from_date', 'before_or_equal:'.date('Y-m-d', strtotime($from_date.' +92 days'))],
        ], [
            'to_date.before_or_equal' => 'Please select a range of up to 3 months.',
        ])->validate();

        Gate::authorize('update', RoomType::findOrFail($roomType_id));

        $baseRates = BaseRate::where(['roomType_id'=>$roomType_id,'currency_type_id'=>$currency_id])
            ->whereBetween('rate_for', [$from_date, $to_date])
            ->get();
        $mealtypes = MealType::all();
        $occupancies = Occupancy::all();
        $dates = [];
        $data = [];
        $start = new DateTime($from_date);
        $end   = new DateTime($to_date); // Note: The end date is excluded by default

        $interval = new DateInterval('P1D'); // P1D represents a period of 1 Day
        $period   = new DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
                // Convert the object to a string format (Y-m-d)
                $formattedDate = $date->format('Y-m-d');

                foreach($baseRates as $baseRate){
                    // Use the string $formattedDate instead of the object $date
                    if($formattedDate == $baseRate->rate_for){
                        $data[$formattedDate][$baseRate->occupancy_id][$baseRate->mealType_id] = $baseRate->rate;
                    }


                }

                foreach($occupancies as $occupancy){

                    foreach($mealtypes as $mealtype){
                            if(!isset($data[$formattedDate])||!isset($data[$formattedDate][$occupancy->id])||!isset($data[$formattedDate][$occupancy->id][$mealtype->id])){
                                $data[$formattedDate][$occupancy->id][$mealtype->id] = 0;
                    }
                    }
                    }
            }

        $symbol = " (".e(CurrencyType::findOrFail($currency_id)->symbol).")";
        $str = '<table class="table table-striped">
                        <thead>
                          <tr><th>Occupancy</th>';
                          foreach ($period as $date) {
                            $formattedDate = $date->format('Y-m-d');
                            foreach($mealtypes as $mealtype){
                            $str .= '<th>'.e($mealtype->mealType).'<br/>('.$formattedDate.') </th>';
                            }
                          }
                          $str .= '</tr>
                        </thead>
                        <tbody>';
                            foreach($occupancies as $occupancy){
                          $str .= '<tr><td class="py-1">'.e($occupancy->occupancy_name).$symbol.'</td>';
                          foreach ($data as $formattedDate => $vals) {

                            foreach($mealtypes as $mealtype){
                            $str .= '<td>
                                <input type="number" step="0.01" min="0" value="'.e($vals[$occupancy->id][$mealtype->id]).'" mealtype_id="'.$mealtype->id.'" occupancy_id="'.$occupancy->id.'" rate_for="'.$formattedDate.'" currency_type_id="'.(int)$currency_id.'" roomType_id="'.(int)$roomType_id.'" class="form-control rate_field" style="width:100px;"/>
                                <div class="spinner-border text-success" role="status" style="display:none">
                                <span class="sr-only">Loading...</span>
                                </div>
                                </td>';
                            }}
                          $str .= '</tr>';
                            }

                        $str .= '</tbody>
                      </table>';

                return response()->json([
                'status' => 'success',
                'html' => $str,
            ]);

    }

    public function updateBaseRates(Request $request){
        $request->validate([
            'occupancy_id' => ['required', 'integer', 'exists:occupancies,id'],
            'mealtype_id' => ['required', 'integer', 'exists:mealtypes,id'],
            'roomType_id' => ['required', 'integer'],
            'currency_type_id' => ['required', 'integer', 'exists:currency_types,id'],
            'rate_for' => ['required', 'date_format:Y-m-d'],
            'rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $roomType = RoomType::findOrFail($request->roomType_id);
        Gate::authorize('update', $roomType);

        $baseRate_id = RateType::where(['property_id'=>$roomType->property_id,'rateType'=>'base_rate'])->firstOrFail()->id;
        $baseRate = BaseRate::where(['roomType_id'=>$roomType->id,'rateType_id'=>$baseRate_id,'mealType_id'=>$request->mealtype_id,'occupancy_id'=>$request->occupancy_id,'currency_type_id'=>$request->currency_type_id,'rate_for'=>$request->rate_for])->first();
        if(!$baseRate){
             $baseRate = new BaseRate();
             $baseRate->roomType_id = $roomType->id;
             $baseRate->rateType_id = $baseRate_id;
             $baseRate->mealType_id = $request->mealtype_id;
             $baseRate->occupancy_id = $request->occupancy_id;
             $baseRate->currency_type_id = $request->currency_type_id;
             $baseRate->rate_for = $request->rate_for;
        }
        $baseRate->rate = $request->rate;
        $baseRate->save();
        return response()->json([
                'status' => 'success'
            ]);
        }

        // Owner's exchange rates (LKR per 1 unit) for every active foreign currency
        public function editExchangeRates(){
            $currencies = CurrencyType::active()->where('is_base', false)->orderBy('id')->get();
            $ownerId = auth()->user()->id;

            $latest = $currencies->mapWithKeys(fn ($currency) => [
                $currency->id => CurrencyRate::where(['property_owner_id'=>$ownerId,'currency_type_id'=>$currency->id])
                    ->orderByDesc('rate_date')->orderByDesc('id')->first(),
            ]);

            return view('currency.exchangeRates', compact('currencies', 'latest'));
        }

        // Each update is a new row so the history of rates is kept
        public function updateExchangeRates(Request $request){
            $currencyIds = CurrencyType::active()->where('is_base', false)->pluck('id');

            $request->validate([
                'rates' => ['required', 'array'],
                'rates.*' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            ]);

            foreach ($request->rates as $currencyId => $rate) {
                if (!$currencyIds->contains((int) $currencyId)) {
                    continue;
                }
                $currencyRate = new CurrencyRate();
                $currencyRate->currency_type_id = $currencyId;
                $currencyRate->property_owner_id = auth()->user()->id;
                $currencyRate->rate_date = now()->toDateString();
                $currencyRate->rate = $rate;
                $currencyRate->save();
            }

            session()->flash('success', 'Exchange rates were updated successfully');

            return redirect()->back();
        }
}
