<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyAmenityXref;
use App\Models\PropertyRoomType;
use App\Models\RoomAmenityXref;
use App\Models\RoomType;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Owners tick the amenities of each property and room type from the admin's
 * list, grouped by category (room amenities follow Expedia's groups).
 * Unticking deactivates the link; nothing is deleted.
 */
class AmenityController extends Controller
{
    // Every checklist the user can manage: each property and the room types it offers
    public function amenities(){
        $properties = Property::with('city.district')
            ->whereIn('id', auth()->user()->accessiblePropertyIds())
            ->orderBy('property_name')
            ->get();

        $roomTypesByProperty = PropertyRoomType::with('roomType')
            ->whereIn('property_id', $properties->pluck('id'))
            ->where('is_active', true)
            ->get()
            ->filter(fn ($row) => $row->roomType?->is_active)
            ->groupBy('property_id');

        $counts = [
            'property' => PropertyAmenityXref::where('is_active', true)->whereIn('property_id', $properties->pluck('id'))
                ->selectRaw('property_id, COUNT(*) as total')->groupBy('property_id')->pluck('total', 'property_id'),
            'roomType' => RoomAmenityXref::where('is_active', true)
                ->whereIn('roomType_id', $roomTypesByProperty->collapse()->pluck('roomType_id'))
                ->selectRaw('roomType_id, COUNT(*) as total')->groupBy('roomType_id')->pluck('total', 'roomType_id'),
        ];

        return view('amenity.index', compact('properties', 'roomTypesByProperty', 'counts'));
    }

    public function propertyAmenities($property_id){
        $property = $this->authorizeTarget('property', $property_id);
        $selected = PropertyAmenityXref::where(['property_id' => $property->id, 'is_active' => true])->pluck('amenity_id')->all();

        return $this->checklist($property, null, $selected);
    }

    public function roomTypeAmenities($property_id, $roomType_id){
        [$property, $roomType] = $this->resolvePropertyRoomType($property_id, $roomType_id, 'update');
        $this->authorizeTarget('roomType', $roomType->id);
        $selected = RoomAmenityXref::where(['roomType_id' => $roomType->id, 'is_active' => true])->pluck('amenity_id')->all();

        return $this->checklist($property, $roomType, $selected);
    }

    public function savePropertyAmenities(Request $request, $property_id){
        $property = $this->authorizeTarget('property', $property_id);
        $ids = $this->validIds($request, 'property');

        $this->sync(PropertyAmenityXref::class, 'property_id', $property->id, 'property', $ids);
        Audit::log('amenities.saved', $property, count($ids).' amenities ticked for the property', null, $property->id);

        return redirect()->back()->with('success', 'Amenities of '.$property->property_name.' were saved.');
    }

    public function saveRoomTypeAmenities(Request $request, $property_id, $roomType_id){
        [$property, $roomType] = $this->resolvePropertyRoomType($property_id, $roomType_id, 'update');
        $this->authorizeTarget('roomType', $roomType->id);
        $ids = $this->validIds($request, 'roomType');

        $this->sync(RoomAmenityXref::class, 'roomType_id', $roomType->id, 'roomType', $ids);
        Audit::log('amenities.saved', $roomType, count($ids).' amenities ticked for room type '.$roomType->roomType, null, $property->id);

        return redirect()->back()->with('success', 'Room amenities of '.$roomType->roomType.' were saved.');
    }

    private function checklist(Property $property, ?RoomType $roomType, array $selected){
        $type = $roomType ? 'roomType' : 'property';
        $groups = Amenity::grouped($type, Amenity::where(['amenity_type' => $type, 'is_active' => true])->get());

        // Other checklists of this property, for quick switching
        $roomTypes = RoomType::where('is_active', true)
            ->whereIn('id', PropertyRoomType::where(['property_id' => $property->id, 'is_active' => true])->select('roomType_id'))
            ->orderBy('roomType')->get();

        return view('amenity.checklist', compact('property', 'roomType', 'groups', 'selected', 'roomTypes'));
    }

    // Ticked ids that are active amenities of the right type
    private function validIds(Request $request, string $type): array
    {
        $request->validate([
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer'],
        ]);

        return Amenity::where(['amenity_type' => $type, 'is_active' => true])
            ->whereIn('id', array_map('intval', $request->input('amenity_ids', [])))
            ->pluck('id')->all();
    }

    // Ticked: link (re)activated; unticked active amenities: link deactivated
    private function sync(string $xrefClass, string $column, int $ownerId, string $type, array $ids): void
    {
        DB::transaction(function () use ($xrefClass, $column, $ownerId, $type, $ids) {
            foreach ($ids as $amenityId) {
                $xrefClass::updateOrCreate([$column => $ownerId, 'amenity_id' => $amenityId], ['is_active' => true]);
            }
            $xrefClass::where([$column => $ownerId, 'is_active' => true])
                ->whereNotIn('amenity_id', $ids ?: [0])
                ->whereIn('amenity_id', Amenity::where(['amenity_type' => $type, 'is_active' => true])->select('id'))
                ->update(['is_active' => false]);
        });
    }
}
