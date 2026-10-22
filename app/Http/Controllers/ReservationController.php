<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\CurrencyType;
use App\Models\MealType;
use App\Models\Occupancy;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomType;
use App\Services\BookingQuotes;
use App\Services\Reservations;
use App\Support\BusinessDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Reservations for property owners (their properties) and admins (all).
 */
class ReservationController extends Controller
{
    public function __construct(private Reservations $reservations, private BookingQuotes $quotes)
    {
    }

    // FR-11: search by reference / guest, filter by property, status and check-in dates
    public function index(Request $request){
        $properties = $this->visibleProperties();

        $reservations = Reservation::with('property', 'currency')
            ->whereIn('property_id', $properties->pluck('id'))
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->where('check_in', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->where('check_in', '<=', $request->to))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(fn ($w) => $w->where('reference', 'like', $term)
                    ->orWhere('guest_name', 'like', $term)
                    ->orWhere('guest_email', 'like', $term)
                    ->orWhere('guest_phone', 'like', $term));
            })
            ->orderByDesc('check_in')
            ->paginate(25)
            ->withQueryString();

        return view('reservations.index', [
            'reservations' => $reservations,
            'properties' => $properties,
            'statuses' => ReservationStatus::cases(),
        ]);
    }

    public function show($reference){
        $reservation = Reservation::with('property', 'currency', 'guestProfile', 'rooms.roomType', 'rooms.occupancy', 'rooms.mealType',
            'rooms.discounts', 'rooms.activeAssignments.room', 'statusHistory.user')
            ->where('reference', $reference)->firstOrFail();
        Gate::authorize('view', $reservation);

        // Free room numbers per line, for check-in
        $freeRooms = [];
        if ($reservation->status === ReservationStatus::Confirmed) {
            $occupied = RoomAssignment::whereNull('released_at')->pluck('room_id');
            foreach ($reservation->rooms as $line) {
                $freeRooms[$line->id] = Room::where(['property_id' => $reservation->property_id, 'roomType_id' => $line->roomType_id, 'is_active' => true])
                    ->whereNotIn('id', $occupied)
                    ->orderBy('room_number')
                    ->get();
            }
        }

        $previousStays = Reservation::where('guest_profile_id', $reservation->guest_profile_id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('property_id', $this->visibleProperties()->pluck('id'))
            ->count();

        return view('reservations.show', [
            'reservation' => $reservation,
            'freeRooms' => $freeRooms,
            'previousStays' => $previousStays,
            'canManage' => Gate::allows('manage', $reservation),
            'today' => BusinessDate::today(),
        ]);
    }

    public function changeStatus(Request $request, $reference){
        $reservation = Reservation::where('reference', $reference)->firstOrFail();
        Gate::authorize('manage', $reservation);

        $request->validate([
            // Check-in has its own action (room numbers are required)
            'status' => ['required', Rule::in([ReservationStatus::Cancelled->value, ReservationStatus::NoShow->value, ReservationStatus::CheckedOut->value, ReservationStatus::Confirmed->value])],
            'note' => ['nullable', 'string', 'max:500', 'required_if:status,cancelled'],
        ], [
            'note.required_if' => 'Please give a reason for the cancellation.',
        ]);

        $to = ReservationStatus::from($request->status);
        $this->reservations->transition($reservation, $to, $request->user(), $request->note);

        return redirect()->back()->with('success', "Booking {$reservation->reference} is now {$to->label()}.");
    }

    public function checkIn(Request $request, $reference){
        $reservation = Reservation::where('reference', $reference)->firstOrFail();
        Gate::authorize('manage', $reservation);

        $request->validate(['rooms' => ['required', 'array']]);
        $this->reservations->checkIn($reservation, $request->rooms, $request->user());

        return redirect()->back()->with('success', "Booking {$reservation->reference} is checked in.");
    }

    // Owner enters a booking (walk-in, phone, email)
    public function create(Request $request){
        $properties = $this->visibleProperties()->where('is_active', true)->values();
        return view('reservations.create', [
            'properties' => $properties,
            'roomTypes' => $properties->isEmpty() ? collect() : $this->ownerRoomTypes($properties->first()),
            'occupancies' => Occupancy::orderBy('occupancy')->get(),
            'mealTypes' => MealType::orderBy('id')->get(),
            'currencies' => CurrencyType::whereIn('code', [CurrencyType::LKR, CurrencyType::USD])->orderBy('id')->get(),
            'today' => BusinessDate::today(),
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'roomType_id' => ['required', 'integer'],
            'occupancy_id' => ['required', 'integer', 'exists:occupancies,id'],
            'mealType_id' => ['required', 'integer', 'exists:mealtypes,id'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'rooms' => ['required', 'integer', 'min:1', 'max:'.BookingQuotes::MAX_ROOMS],
            'currency' => ['required', Rule::in([CurrencyType::LKR, CurrencyType::USD])],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:50'],
            'guest_address' => ['nullable', 'string', 'max:500'],
            'guest_country' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        [$property, $roomType] = $this->resolvePropertyRoomType($data['property_id'], $data['roomType_id'], 'update');

        $reservation = $this->reservations->create(
            $property,
            [['roomType' => $roomType, 'occupancy_id' => (int) $data['occupancy_id'], 'mealType_id' => (int) $data['mealType_id'], 'rooms' => (int) $data['rooms']]],
            $data['check_in'],
            $data['check_out'],
            CurrencyType::byCode($data['currency']),
            [
                'name' => $data['guest_name'],
                'email' => $data['guest_email'],
                'phone' => $data['guest_phone'],
                'address' => $data['guest_address'] ?? null,
                'country' => $data['guest_country'] ?? null,
                'special_requests' => $data['special_requests'] ?? null,
            ],
            PaymentMethod::PayAtHotel,
            null,
            $request->user(),
            'owner',
        );

        return redirect('/reservations/'.$reservation->reference)->with('success', "Booking {$reservation->reference} was created.");
    }

    // Owner: their properties; admin: all properties
    private function visibleProperties()
    {
        return Property::when(!auth()->user()->isAdmin(), fn ($q) => $q->where('property_owner_id', auth()->id()))
            ->orderBy('property_name')
            ->get();
    }
}
