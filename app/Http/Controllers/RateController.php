<?php

namespace App\Http\Controllers;


use App\Models\Property;
use App\Models\RoomType;
use App\Models\BaseRate;
use App\Models\MealType;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Occupancy;
use App\Models\CurrencyType;
use App\Models\RateType;
use App\Models\CurrencyRate;

class RateController extends Controller
{
    // Rates calendar holds a USD (required) and an LKR price per cell
    const RATE_CURRENCIES = [CurrencyType::USD, CurrencyType::LKR];
    const MAX_DAYS = 31;

    public function addBaseRate($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('update', $property);
        $roomTypes = RoomType::where(['property_id'=>$property_id,'is_active'=>true])->get();
        return view('rate.addBaseRate', compact('property','roomTypes'));
    }

    public function getBaseRates($roomType_id,$from_date,$to_date){
        Validator::make(compact('from_date','to_date'), [
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date', 'before:'.date('Y-m-d', strtotime($from_date.' +'.self::MAX_DAYS.' days'))],
        ], [
            'to_date.before' => 'Please select a range of up to '.self::MAX_DAYS.' days.',
        ])->validate();

        $roomType = RoomType::findOrFail($roomType_id);
        Gate::authorize('update', $roomType);

        $currencies = CurrencyType::whereIn('code', self::RATE_CURRENCIES)->pluck('code', 'id');
        $dates = collect(CarbonPeriod::create($from_date, $to_date))->map(fn ($date) => $date->toDateString())->all();

        // $grid[date][occupancy_id][mealType_id][currency code] = rate (only rates that are set)
        $grid = [];
        BaseRate::where('roomType_id', $roomType->id)
            ->whereIn('currency_type_id', $currencies->keys())
            ->whereBetween('rate_for', [$from_date, $to_date])
            ->where('rate', '>', 0)
            ->get()
            ->each(function ($rate) use (&$grid, $currencies) {
                $grid[substr($rate->rate_for, 0, 10)][$rate->occupancy_id][$rate->mealType_id][$currencies[$rate->currency_type_id]] = (float) $rate->rate;
            });

        $html = view('rate._grid', [
            'roomType' => $roomType,
            'dates' => $dates,
            'grid' => $grid,
            'mealTypes' => MealType::orderBy('id')->get(),
            'occupancies' => Occupancy::where('occupancy', '<=', $roomType->max_occupancy)->orderBy('occupancy')->get(),
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html]);
    }

    /**
     * Saves the whole grid. Input: roomType_id, rates[date][occupancy_id][mealType_id][USD|LKR].
     * A combination may be left empty; if it has any price, the USD price is required.
     * Clearing a price sets it to 0 ("not offered"); rates are never deleted.
     */
    public function saveBaseRates(Request $request){
        $request->validate([
            'roomType_id' => ['required', 'integer'],
            'rates' => ['required', 'array', 'max:'.self::MAX_DAYS],
        ]);

        $roomType = RoomType::findOrFail($request->roomType_id);
        Gate::authorize('update', $roomType);

        $currencies = CurrencyType::whereIn('code', self::RATE_CURRENCIES)->pluck('id', 'code');
        $occupancyNames = Occupancy::pluck('occupancy_name', 'id');
        $mealTypeNames = MealType::pluck('mealType', 'id');

        // Validate every cell and collect the values to save
        $errors = [];
        $values = []; // [date][occupancy][meal][code] = float|null
        foreach ($request->rates as $date => $occupancies) {
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date) || !is_array($occupancies)) {
                $errors[] = 'Invalid date in rates.';
                continue;
            }
            foreach ($occupancies as $occupancyId => $meals) {
                foreach ((array) $meals as $mealTypeId => $prices) {
                    if (!isset($occupancyNames[$occupancyId], $mealTypeNames[$mealTypeId])) {
                        $errors[] = 'Invalid occupancy or meal plan in rates.';
                        continue;
                    }
                    $label = "$date, {$occupancyNames[$occupancyId]} / {$mealTypeNames[$mealTypeId]}";
                    $cell = [];
                    foreach (self::RATE_CURRENCIES as $code) {
                        $raw = trim((string) ($prices[$code] ?? ''));
                        if ($raw === '' || (is_numeric($raw) && (float) $raw == 0)) {
                            $cell[$code] = null;
                        } elseif (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > 99999999) {
                            $errors[] = "$label: $code price must be a positive amount.";
                        } else {
                            $cell[$code] = round((float) $raw, 2);
                        }
                    }
                    if (($cell['LKR'] ?? null) !== null && ($cell['USD'] ?? null) === null) {
                        $errors[] = "$label: the USD price is required.";
                    }
                    $values[$date][(int) $occupancyId][(int) $mealTypeId] = $cell;
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['rates' => array_values(array_unique($errors))]);
        }

        $rateTypeId = RateType::where(['property_id'=>$roomType->property_id,'rateType'=>'base_rate'])->firstOrFail()->id;

        DB::transaction(function () use ($values, $roomType, $rateTypeId, $currencies) {
            $existing = BaseRate::where(['roomType_id'=>$roomType->id,'rateType_id'=>$rateTypeId])
                ->whereIn('rate_for', array_keys($values))
                ->whereIn('currency_type_id', $currencies->values())
                ->get()
                ->keyBy(fn ($rate) => substr($rate->rate_for, 0, 10).'|'.$rate->occupancy_id.'|'.$rate->mealType_id.'|'.$rate->currency_type_id);

            foreach ($values as $date => $occupancies) {
                foreach ($occupancies as $occupancyId => $meals) {
                    foreach ($meals as $mealTypeId => $cell) {
                        foreach ($cell as $code => $amount) {
                            $baseRate = $existing["$date|$occupancyId|$mealTypeId|{$currencies[$code]}"] ?? null;

                            if (!$baseRate) {
                                if ($amount === null) {
                                    continue; // nothing to store
                                }
                                $baseRate = new BaseRate();
                                $baseRate->roomType_id = $roomType->id;
                                $baseRate->rateType_id = $rateTypeId;
                                $baseRate->mealType_id = $mealTypeId;
                                $baseRate->occupancy_id = $occupancyId;
                                $baseRate->currency_type_id = $currencies[$code];
                                $baseRate->rate_for = $date;
                            } elseif ((float) $baseRate->rate === (float) ($amount ?? 0)) {
                                continue; // unchanged
                            }

                            $baseRate->rate = $amount ?? 0;
                            $baseRate->save();
                        }
                    }
                }
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Rates were saved successfully']);
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
