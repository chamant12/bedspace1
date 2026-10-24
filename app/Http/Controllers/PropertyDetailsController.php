<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Photo;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\Room;
use App\Services\Inventory;
use App\Services\RatesCalendar;
use App\Support\BusinessDate;
use Illuminate\Support\Facades\Gate;

/**
 * Read-only overview of one property: details, people, location, photos,
 * amenities, cancellation policy and, per room type, today's inventory,
 * rates and meal plans. Visible to whoever may view the property
 * (owner, admin; property staff via PropertyPolicy::view).
 */
class PropertyDetailsController extends Controller
{
    public function __construct(private Inventory $inventory, private RatesCalendar $rates)
    {
    }

    public function show($property_id){
        $property = Property::with('city.district.province', 'propertyOwner', 'primaryContact.user')->findOrFail($property_id);
        Gate::authorize('view', $property);

        $today = BusinessDate::today();
        $mealTypes = $this->rates->mealTypes()->keyBy('id');
        $totals = PropertyRoomType::where('property_id', $property->id)->pluck('total_rooms', 'roomType_id');
        $roomNumbers = Room::where(['property_id' => $property->id, 'is_active' => true])->whereNotNull('room_number')
            ->selectRaw('roomType_id, COUNT(*) as total')->groupBy('roomType_id')->pluck('total', 'roomType_id');

        $roomTypes = [];
        foreach ($this->ownerRoomTypes($property) as $roomType) {
            $day = $this->inventory->load($property, $roomType, $today, $today)['days'][$today];
            $grid = $this->rates->load($property, $roomType, $today, $today)['grid'][$today] ?? [];

            // Skip the owner's room types that are not set up at this property
            if ((int) ($totals[$roomType->id] ?? 0) === 0 && !$day['open']) {
                continue;
            }

            // Today's complete combinations (USD + LKR), and the meal plans they cover
            $rates = [];
            foreach ($this->rates->occupancies($roomType) as $occupancy) {
                foreach ($mealTypes as $mealType) {
                    $prices = $grid[$occupancy->id][$mealType->id] ?? [];
                    if (isset($prices['USD'], $prices['LKR'])) {
                        $rates[] = ['occupancy' => $occupancy, 'mealType' => $mealType, 'usd' => $prices['USD'], 'lkr' => $prices['LKR']];
                    }
                }
            }

            $roomTypes[] = [
                'roomType' => $roomType,
                'total' => (int) ($totals[$roomType->id] ?? 0),
                'closed' => $day['closed'],
                'booked' => $day['booked'],
                'available' => $day['available'],
                'open' => $day['open'],
                'roomNumbers' => (int) ($roomNumbers[$roomType->id] ?? 0),
                'rates' => $rates,
                'mealPlans' => collect($rates)->pluck('mealType')->unique('id')->sortBy('id')->values(),
                'amenities' => Amenity::whereIn('id', fn ($q) => $q->select('amenity_id')->from('roomType_amenity_xrefs')
                    ->where(['roomType_id' => $roomType->id, 'is_active' => true]))->where('is_active', true)->orderBy('amenity')->pluck('amenity'),
            ];
        }

        $photos = Photo::where(['upload_type' => 'property', 'type_id' => $property->id, 'is_active' => true])->orderBy('sort_order')->get();
        $amenities = Amenity::whereIn('id', fn ($q) => $q->select('amenity_id')->from('property_amenity_xrefs')
            ->where(['property_id' => $property->id, 'is_active' => true]))->where('is_active', true)->orderBy('amenity')->pluck('amenity');

        return view('property.details', [
            'property' => $property,
            'today' => $today,
            'roomTypes' => $roomTypes,
            'photos' => $photos,
            'amenities' => $amenities,
            'canEdit' => Gate::allows('update', $property) && auth()->user()->isPartner(),
        ]);
    }
}
