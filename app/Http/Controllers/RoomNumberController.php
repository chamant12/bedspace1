<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Room numbers of a property. Kept separate from inventory counts; a guest
 * is assigned one at check-in. Room numbers are deactivated, never deleted.
 */
class RoomNumberController extends Controller
{
    const MAX_PER_REQUEST = 200;

    public function index($property_id){
        $property = Property::findOrFail($property_id);
        Gate::authorize('manageInventory', $property);

        $rooms = Room::with('roomType')
            ->where('property_id', $property->id)
            ->whereNotNull('room_number')
            ->orderBy('room_number')
            ->get();
        $roomTypes = $this->ownerRoomTypes($property);
        $totals = PropertyRoomType::where('property_id', $property->id)->pluck('total_rooms', 'roomType_id');
        $numbered = $rooms->where('is_active', true)->countBy('roomType_id');

        return view('rooms.index', compact('property', 'rooms', 'roomTypes', 'totals', 'numbered'));
    }

    // room_numbers: "101-110, 201, A1" (ranges keep their prefix and zero padding)
    public function store(Request $request){
        $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'room_numbers' => ['required', 'string', 'max:2000'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($request->property_id, $request->roomType_id, 'manageInventory');

        $numbers = $this->parseRoomNumbers($request->room_numbers);

        $existing = Room::where('property_id', $property->id)->whereIn('room_number', $numbers)->pluck('room_number');
        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages(['room_numbers' => 'Already used at this property: '.$existing->join(', ')]);
        }

        DB::transaction(function () use ($numbers, $property, $roomType) {
            foreach ($numbers as $number) {
                $room = new Room();
                $room->property_id = $property->id;
                $room->roomType_id = $roomType->id;
                $room->room_number = $number;
                $room->save();
            }
        });

        session()->flash('success', count($numbers).' room number(s) added to '.$roomType->roomType);
        return redirect()->back();
    }

    public function update(Request $request, $room_id){
        $room = Room::findOrFail($room_id);
        $request->validate([
            'room_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\/ ]+$/'],
            'roomType_id' => ['required', 'integer'],
        ]);
        [$property, $roomType] = $this->resolvePropertyRoomType($room->property_id, $request->roomType_id, 'manageInventory');

        $number = trim($request->room_number);
        $taken = Room::where('property_id', $property->id)->where('room_number', $number)->where('id', '!=', $room->id)->exists();
        if ($taken) {
            throw ValidationException::withMessages(['room_number' => "Room $number already exists at this property."]);
        }

        $room->room_number = $number;
        $room->roomType_id = $roomType->id;
        $room->save();

        session()->flash('success', "Room $number was updated");
        return redirect()->back();
    }

    public function toggleActive($room_id){
        $room = Room::findOrFail($room_id);
        Gate::authorize('manageInventory', Property::findOrFail($room->property_id));

        $room->is_active = !$room->is_active;
        $room->save();

        session()->flash('success', "Room {$room->room_number} was ".($room->is_active ? 'activated' : 'deactivated'));
        return redirect()->back();
    }

    private function parseRoomNumbers(string $input): array
    {
        $numbers = [];
        $errors = [];

        foreach (preg_split('/[,;\n]+/', $input) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            // Range such as 101-110 or A01-A10 (same prefix on both sides)
            if (preg_match('/^([A-Za-z]*)(\d+)\s*-\s*([A-Za-z]*)(\d+)$/', $token, $m) && strcasecmp($m[1], $m[3]) === 0) {
                [$prefix, $start, $end] = [$m[1], (int) $m[2], (int) $m[4]];
                if ($end < $start || $end - $start >= self::MAX_PER_REQUEST) {
                    $errors[] = "Invalid range $token.";
                    continue;
                }
                $width = strlen($m[2]);
                for ($n = $start; $n <= $end; $n++) {
                    $numbers[] = $prefix.str_pad((string) $n, $width, '0', STR_PAD_LEFT);
                }
                continue;
            }

            if (strlen($token) > 20 || !preg_match('/^[A-Za-z0-9\-\/ ]+$/', $token)) {
                $errors[] = "Invalid room number \"$token\" (letters, digits, - and / only, up to 20 characters).";
                continue;
            }
            $numbers[] = $token;
        }

        $numbers = array_values(array_unique($numbers));

        if (!$numbers && !$errors) {
            $errors[] = 'Please enter at least one room number.';
        }
        if (count($numbers) > self::MAX_PER_REQUEST) {
            $errors[] = 'Please add at most '.self::MAX_PER_REQUEST.' room numbers at a time.';
        }
        if ($errors) {
            throw ValidationException::withMessages(['room_numbers' => $errors]);
        }

        return $numbers;
    }
}
