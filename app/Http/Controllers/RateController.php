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
use App\Models\Occupancy;
use App\Models\CurrencyType;
use App\Models\RateType;
use App\Models\CurrencyRate;

class RateController extends Controller
{
    public function addBaseRate($property_id){
        $property = Property::where('id',$property_id)->first();
        $roomTypes = RoomTYpe::where('property_id',$property_id)->get();
        $currencies = CurrencyType::all();
        return view('rate.addBaseRate', compact('property','roomTypes','currencies'));
    }

    public function getBaseRates($roomType_id,$currency_id,$from_date,$to_date){
        $baseRates = BaseRate::where(['roomTYpe_id'=>$roomType_id,'currency_type_id'=>$currency_id])->get();
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

        $symbol = ($currency_id==1)?"(Rs.)":"($)";
        $str = '<table class="table table-striped">
                        <thead>
                          <tr><th>Occupancy</th>';
                          foreach ($period as $date) {
                            $formattedDate = $date->format('Y-m-d');
                            foreach($mealtypes as $mealtype){
                            $str .= '<th>'.$mealtype->mealType.'<br/>('.$formattedDate.') </th>';
                            }
                          }
                          $str .= '</tr>
                        </thead>
                        <tbody>';
                            foreach($occupancies as $occupancy){
                          $str .= '<tr><td class="py-1">'.$occupancy->occupancy_name.$symbol.'</td>';
                          foreach ($data as $formattedDate => $vals) {

                            foreach($mealtypes as $mealtype){
                            $str .= '<td>
                                <input type="number" step="0.01" min="0" value="'.$vals[$occupancy->id][$mealtype->id].'" mealtype_id="'.$mealtype->id.'" occupancy_id="'.$occupancy->id.'" rate_for="'.$formattedDate.'" currency_type_id="'.$currency_id.'" roomType_id="'.$roomType_id.'" class="form-control rate_field" style="width:100px;"/>
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

    public function updateBaseRates(){
        $occupancy_id = request()->occupancy_id;
        $mealType_id = request()->mealtype_id;
        $roomType_id = request()->roomType_id;
        $currency_type_id = request()->currency_type_id;
        $rate_for = request()->rate_for;
        $rate = request()->rate;
        $property_id = RoomTYpe::where('id',$roomType_id)->first()->property_id;
        $baseRate_id = RateType::where(['property_id'=>$property_id,'rateType'=>'base_rate'])->first()->id;
        $baseRate = BaseRate::where(['roomType_id'=>$roomType_id,'rateType_id'=>$baseRate_id,'mealType_id'=>$mealType_id,'occupancy_id'=>$occupancy_id,'currency_type_id'=>$currency_type_id,'rate_for'=>$rate_for])->first();    
        if(!$baseRate){
             $baseRate = new BaseRate();
             $baseRate->roomType_id = $roomType_id;
             $baseRate->rateType_id = $baseRate_id;
             $baseRate->mealType_id = $mealType_id;
             $baseRate->occupancy_id = $occupancy_id;
             $baseRate->currency_type_id = $currency_type_id;
             $baseRate->rate_for = $rate_for;
        }
        $baseRate->rate = $rate;
        $baseRate->save();
        return response()->json([
                'status' => 'success'
            ]);
        }

        public function editDollarRate(){
            $currencyRate = CurrencyRate::where(['currency_type_id'=>2,'rate_date'=>date('Y-m-d'),'property_owner_id'=>auth()->user()->id])->orderBy('id','DESC')->first();

            if(!$currencyRate){
                $currencyRate = new CurrencyRate();
                $currencyRate->rate = 3000;
            }

            return view('currency.editCurrenxyRate',compact('currencyRate'));
        }

        public function updateDollarRate(){
            $currencyRate = CurrencyRate::where(['currency_type_id'=>2,'rate_date'=>date('Y-m-d'),'property_owner_id'=>auth()->user()->id])->orderBy('id','DESC')->first();
            if(!$currencyRate){
                $currencyRate = new CurrencyRate();
                $currencyRate->currency_type_id = 2;
                $currencyRate->rate_date = date('Y-m-d');
                $currencyRate->property_owner_id = auth()->user()->id;
            }
            $currencyRate->rate = request()->rate;
            $currencyRate->save();

            session()->flash('success', 'Dollar rate was updated successfully');

            return redirect()->back();
        }
}
