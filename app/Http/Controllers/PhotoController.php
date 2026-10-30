<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\RoomType;
use App\Services\ImageUploader;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Property and room-type photo galleries: upload, reorder by drag and drop,
 * remove (hidden, never deleted). The first photo is the cover image.
 * Room types are owner-level, so a room type's gallery is shared by all
 * the owner's properties that offer it.
 */
class PhotoController extends Controller
{
    // Every gallery the user can manage: each property and the room types it offers
    public function selectPhotoType(){
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

        // Photo count per gallery, keyed "property:5" / "roomType:3"
        $counts = Photo::where('is_active', true)
            ->selectRaw('upload_type, type_id, COUNT(*) as total')
            ->groupBy('upload_type', 'type_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->upload_type.':'.$row->type_id => (int) $row->total]);

        return view('photo.index', compact('properties', 'roomTypesByProperty', 'counts'));
    }

    public function propertyGallery($property_id){
        $property = $this->authorizeTarget('property', $property_id);
        return $this->gallery($property, null);
    }

    public function roomTypeGallery($property_id, $roomType_id){
        [$property, $roomType] = $this->resolvePropertyRoomType($property_id, $roomType_id, 'update');
        $this->authorizeTarget('roomType', $roomType->id);
        return $this->gallery($property, $roomType);
    }

    private function gallery(Property $property, ?RoomType $roomType){
        $photos = Photo::where([
            'upload_type' => $roomType ? 'roomType' : 'property',
            'type_id' => $roomType ? $roomType->id : $property->id,
            'is_active' => true,
        ])->orderBy('sort_order')->orderBy('id')->get();

        // Other galleries of this property, for quick switching
        $roomTypes = RoomType::where('is_active', true)
            ->whereIn('id', PropertyRoomType::where(['property_id' => $property->id, 'is_active' => true])->select('roomType_id'))
            ->orderBy('roomType')->get();

        return view('photo.manage', compact('property', 'roomType', 'photos', 'roomTypes'));
    }

    public function uploadPhotos(Request $request, ImageUploader $uploader)
    {
        $request->validate([
            'photos' => 'required|array|max:20',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'upload_type' => 'required|in:property,roomType',
            'type_id' => 'required|integer',
        ]);

        $uploadType = $request->upload_type;
        $typeId = (int) $request->type_id;
        $this->authorizeTarget($uploadType, $typeId);

        $path = "/{$uploadType}_{$typeId}/";
        // New photos go after the existing ones
        $maxOrder = (int) DB::table('photos')->where(['upload_type' => $uploadType, 'type_id' => $typeId])->max('sort_order');

        foreach ($request->file('photos') as $index => $file) {
            $stored = $uploader->store($file, $path);

            DB::table('photos')->insert([
                'disk' => $stored['disk'],
                'upload_path' => $path,
                'upload_type' => $uploadType,
                'type_id' => $typeId,
                'file_name' => $stored['file_name'],
                'has_thumbnail' => true,
                'sort_order' => $maxOrder + $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['status' => 'success', 'uploaded' => count($request->file('photos'))]);
    }

    /**
     * New order of one gallery: order = [photo ids, first = cover].
     * Must list exactly the gallery's active photos.
     */
    public function updatePhotoOrder(Request $request)
    {
        $request->validate([
            'order' => ['required', 'array', 'min:1', 'max:500'],
            'order.*' => ['integer', 'distinct'],
        ]);
        $ids = array_map('intval', $request->order);

        $first = Photo::where('is_active', true)->find($ids[0]);
        if (!$first) {
            return response()->json(['message' => 'Photo not found.'], 404);
        }
        $this->authorizeTarget($first->upload_type, $first->type_id);

        $galleryIds = Photo::where(['upload_type' => $first->upload_type, 'type_id' => $first->type_id, 'is_active' => true])
            ->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $sorted = $ids;
        sort($sorted);
        if ($sorted !== $galleryIds) {
            return response()->json(['message' => 'The gallery has changed. Please reload the page and try again.'], 409);
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                DB::table('photos')->where('id', $id)->update(['sort_order' => $position + 1, 'updated_at' => now()]);
            }
        });

        Audit::log('photo.reordered', null, "Photos of {$first->upload_type} #{$first->type_id} reordered (cover: photo #{$ids[0]})", null,
            $first->upload_type === 'property' ? (int) $first->type_id : null);

        return response()->json(['status' => 'success']);
    }

    // Photos are hidden (deactivated), never deleted; the files stay in storage
    public function deactivatePhoto($photo_id)
    {
        $photo = Photo::where('is_active', true)->find($photo_id);
        if (!$photo) {
            return response()->json(['message' => 'Photo not found.'], 404);
        }
        $this->authorizeTarget($photo->upload_type, $photo->type_id);

        DB::transaction(function () use ($photo) {
            DB::table('photos')->where('id', $photo->id)->update(['is_active' => false, 'updated_at' => now()]);

            // Close the gap in the order of the remaining photos
            $remaining = DB::table('photos')
                ->where(['upload_type' => $photo->upload_type, 'type_id' => $photo->type_id, 'is_active' => true])
                ->orderBy('sort_order')->orderBy('id')->pluck('id');
            foreach ($remaining as $position => $id) {
                DB::table('photos')->where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });

        Audit::log('photo.removed', null, "Photo #{$photo->id} ({$photo->upload_type} #{$photo->type_id}) removed from the gallery", null,
            $photo->upload_type === 'property' ? (int) $photo->type_id : null);

        return response()->json(['status' => 'success']);
    }
}
