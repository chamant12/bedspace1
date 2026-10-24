<?php

namespace App\Http\Controllers;

use App\Models\GuestProfile;
use App\Models\Reservation;
use Illuminate\Http\Request;

/**
 * Guest records (FR-09 / FR-11): guests who booked at the user's properties,
 * searchable by name, email or phone, with their booking history.
 * Personal data is only shown for guests of properties the user can access.
 */
class GuestController extends Controller
{
    public function index(Request $request){
        $propertyIds = $request->user()->accessiblePropertyIds();

        $guests = GuestProfile::query()
            ->whereIn('id', Reservation::whereIn('property_id', $propertyIds)->select('guest_profile_id'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $digits = preg_replace('/\D/', '', $request->q);
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->when(strlen($digits) >= 7, fn ($x) => $x->orWhere('phone_key', substr($digits, -9))));
            })
            ->withCount(['reservations as bookings_count' => fn ($q) => $q->whereIn('property_id', $propertyIds)])
            ->withMax(['reservations as last_check_in' => fn ($q) => $q->whereIn('property_id', $propertyIds)], 'check_in')
            ->orderByDesc('last_check_in')
            ->paginate(25)
            ->withQueryString();

        return view('guests.index', compact('guests'));
    }

    public function show(Request $request, $guest_id){
        $propertyIds = $request->user()->accessiblePropertyIds();

        $guest = GuestProfile::findOrFail($guest_id);
        $reservations = Reservation::with('property', 'currency')
            ->where('guest_profile_id', $guest->id)
            ->whereIn('property_id', $propertyIds)
            ->orderByDesc('check_in')
            ->get();

        // Only guests who booked at one of the user's properties
        abort_if($reservations->isEmpty(), 404);

        return view('guests.show', compact('guest', 'reservations'));
    }
}
