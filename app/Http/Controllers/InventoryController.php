<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\Inventory;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Inventory calendar: total rooms per property x room type, rooms closed
 * per date, booked rooms (read-only) and resulting availability.
 * All rules live in App\Services\Inventory.
 */
class InventoryController extends Controller
{
    public function __construct(private Inventory $inventory)
    {
    }

    public function index($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('manageInventory', $property);
        return $this->page($property, true);
    }

    public function view($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('viewInventory', $property);
        return $this->page($property, false);
    }

    public function grid($property_id,$roomType_id,$from_date,$to_date){
        return $this->gridResponse('manageInventory', true, $property_id, $roomType_id, $from_date, $to_date);
    }

    public function gridReadonly($property_id,$roomType_id,$from_date,$to_date){
        return $this->gridResponse('viewInventory', false, $property_id, $roomType_id, $from_date, $to_date);
    }

    public function saveTotal(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'total_rooms' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageInventory');

        $this->inventory->setTotalRooms($property, $roomType, (int) $request->total_rooms);

        return response()->json(['status' => 'success', 'message' => "{$property->property_name} now has {$request->total_rooms} {$roomType->roomType} room(s)."]);
    }

    // closed[date] = count, for individual dates
    public function saveClosed(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'closed' => ['required', 'array', 'max:'.Inventory::MAX_VIEW_DAYS],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageInventory');

        $this->inventory->setClosed($property, $roomType, $request->closed);

        return response()->json(['status' => 'success', 'message' => 'Closed rooms were saved successfully']);
    }

    // Same closed count on a date range, optionally only some weekdays
    public function closeRange(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'closed_rooms' => ['required', 'integer', 'min:0'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageInventory');

        $count = $this->inventory->setClosedForRange($property, $roomType, $request->from_date, $request->to_date,
            array_map('intval', $request->weekdays ?? []), (int) $request->closed_rooms);

        return response()->json(['status' => 'success', 'message' => "Closed rooms set to {$request->closed_rooms} on $count date(s)."]);
    }

    private function page(Property $property, bool $editable){
        $roomTypes = $this->ownerRoomTypes($property);
        $today = BusinessDate::today();
        return view('inventory.index', compact('property', 'roomTypes', 'today', 'editable'));
    }

    private function gridResponse(string $ability, bool $editable, $property_id, $roomType_id, $from_date, $to_date){
        Validator::make(compact('from_date','to_date'), [
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ])->validate();

        [$property, $roomType] = $this->resolvePropertyRoomType($property_id, $roomType_id, $ability);

        $html = view('inventory._grid', $this->inventory->load($property, $roomType, $from_date, $to_date) + [
            'property' => $property,
            'roomType' => $roomType,
            'editable' => $editable,
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html]);
    }
}
