<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\PropertyAmenityXref;
use App\Models\RoomAmenityXref;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The amenity master list is maintained by admins only, so owners pick from
 * one shared label per physical amenity instead of creating duplicates.
 */
class AmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::orderBy('amenity_type')->orderBy('amenity')->get();

        // Number of properties / room types using each amenity
        $propertyUsage = PropertyAmenityXref::selectRaw('amenity_id, count(*) as total')->groupBy('amenity_id')->pluck('total', 'amenity_id');
        $roomTypeUsage = RoomAmenityXref::selectRaw('amenity_id, count(*) as total')->groupBy('amenity_id')->pluck('total', 'amenity_id');
        $usage = $amenities->mapWithKeys(fn ($amenity) => [
            $amenity->id => ($propertyUsage[$amenity->id] ?? 0) + ($roomTypeUsage[$amenity->id] ?? 0),
        ]);

        return view('admin.amenities', compact('amenities', 'usage'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $amenity = new Amenity();
        $amenity->amenity = $data['amenity'];
        $amenity->amenity_type = $data['amenity_type'];
        $amenity->save();

        session()->flash('success', 'Amenity added successfully');
        return redirect()->back();
    }

    public function update(Request $request, $amenity_id)
    {
        $amenity = Amenity::findOrFail($amenity_id);
        $data = $this->validated($request, $amenity->id);

        $amenity->amenity = $data['amenity'];
        $amenity->amenity_type = $data['amenity_type'];
        $amenity->save();

        session()->flash('success', 'Amenity updated successfully');
        return redirect()->back();
    }

    public function destroy($amenity_id)
    {
        $amenity = Amenity::findOrFail($amenity_id);

        $inUse = PropertyAmenityXref::where('amenity_id', $amenity->id)->exists()
            || RoomAmenityXref::where('amenity_id', $amenity->id)->exists();

        if ($inUse) {
            session()->flash('error', 'This amenity is in use by one or more properties and cannot be deleted');
            return redirect()->back();
        }

        $amenity->delete();
        session()->flash('success', 'Amenity deleted successfully');
        return redirect()->back();
    }

    // The same name may exist once per type (a property-level and a room-level "Wi-Fi")
    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'amenity_type' => ['required', 'in:property,roomType'],
            'amenity' => [
                'required', 'string', 'max:255',
                Rule::unique('amenities', 'amenity')
                    ->where('amenity_type', $request->amenity_type)
                    ->ignore($ignoreId),
            ],
        ], [
            'amenity.unique' => 'An amenity with this name already exists',
        ]);
    }
}
