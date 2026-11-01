<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RoomType;
use App\Models\Occupancy;
use App\Services\RatesCalendar;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Rates calendar (editable for owners, read-only for other roles).
 * All rules live in App\Services\RatesCalendar.
 */
class RateController extends Controller
{
    public function __construct(private RatesCalendar $calendar)
    {
    }

    public function addBaseRate($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('manageRates', $property);
        $roomTypes = $this->ownerRoomTypes($property);
        $today = BusinessDate::today();
        // For the date-range form: every occupancy (shown up to the room type's maximum) x meal plan
        $occupancies = Occupancy::orderBy('occupancy')->get();
        $mealTypes = $this->calendar->mealTypes();
        return view('rate.addBaseRate', compact('property','roomTypes','today','occupancies','mealTypes'));
    }

    public function viewRates($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('viewRates', $property);
        $roomTypes = $this->ownerRoomTypes($property);
        $today = BusinessDate::today();
        return view('rate.viewRates', compact('property','roomTypes','today'));
    }

    public function getBaseRates($property_id,$roomType_id,$from_date,$to_date){
        return $this->gridResponse('manageRates', true, $property_id, $roomType_id, $from_date, $to_date);
    }

    public function getBaseRatesReadonly($property_id,$roomType_id,$from_date,$to_date){
        return $this->gridResponse('viewRates', false, $property_id, $roomType_id, $from_date, $to_date);
    }

    public function saveBaseRates(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'rates' => ['required', 'array', 'max:'.RatesCalendar::MAX_VIEW_DAYS],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageRates');

        $this->calendar->save($property, $roomType, $request->rates);

        return response()->json(['status' => 'success', 'message' => 'Rates were saved successfully']);
    }

    // Resets one date (date given) or all dates from today (no date)
    public function resetBaseRates(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageRates');

        $this->calendar->reset($property, $roomType, $request->date);

        $message = $request->date
            ? "Rates for {$request->date} were reset. {$roomType->roomType} is closed on that date."
            : "All rates from today on were reset. {$roomType->roomType} is hidden from guests at this property until new rates are added.";

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    public function copyBaseRates(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'source_date' => ['required', 'date_format:Y-m-d'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageRates');

        $count = $this->calendar->copy($property, $roomType, $request->source_date, $request->from_date, $request->to_date,
            array_map('intval', $request->weekdays ?? []));

        return response()->json(['status' => 'success', 'message' => "Rates of {$request->source_date} were copied to $count date(s)."]);
    }

    // The same USD and LKR prices for every date in a range, in one submit
    public function setRangeRates(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'prices' => ['required', 'array'],
        ], [
            'to_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageRates');

        $count = $this->calendar->setRange($property, $roomType, $request->from_date, $request->to_date,
            array_map('intval', $request->weekdays ?? []), $request->prices);

        return response()->json(['status' => 'success', 'message' => "Rates of {$roomType->roomType} were set for $count date(s) from {$request->from_date} to {$request->to_date}."]);
    }

    private function gridResponse(string $ability, bool $editable, $property_id, $roomType_id, $from_date, $to_date){
        Validator::make(compact('from_date','to_date'), [
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ])->validate();

        [$property, $roomType] = $this->resolvePropertyRoomType($property_id, $roomType_id, $ability);
        $calendar = $this->calendar->load($property, $roomType, $from_date, $to_date);

        $html = view('rate._grid', $calendar + [
            'property' => $property,
            'roomType' => $roomType,
            'editable' => $editable,
            'today' => BusinessDate::today(),
            'mealTypes' => $this->calendar->mealTypes(),
            'occupancies' => $this->calendar->occupancies($roomType),
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html]);
    }
}
