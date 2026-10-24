<?php

namespace App\Http\Controllers;
use App\Models\Amenity;
use App\Models\PropertyAmenityXref;
use App\Models\RoomAmenityXref;
use App\Models\RoomType;
use App\Models\Property;
use App\Models\Photo;
use DB;
use Illuminate\Http\Request;
use App\Services\ImageUploader;

class PhotoController extends Controller
{
    public function selectPhotoType(){
        $properties = Property::with('city','city.district')->whereIn('id', auth()->user()->accessiblePropertyIds())->orderBy('property_name')->get();
        return view('photo.phototype',compact('properties'));
    }

    public function managePhotos(){
        $property = $this->authorizeTarget('property', request()->property_id);
        $upload_type = "property";
        $type_id = Request()->property_id;
        $roomType = request()->filled('roomType_id')?$this->authorizeTarget('roomType', request()->roomType_id):null;
        if($roomType!=null){
            $upload_type = "roomType";
            $type_id = Request()->roomType_id;
        }
        $photos = Photo::where(['upload_type'=>$upload_type,'type_id'=>$type_id,'is_active'=>true])->orderBy('sort_order')->get();
        return view('photo.managePhotos',compact('property','roomType','photos'));
    }


        public function uploadPhotos(Request $request, ImageUploader $uploader)
        {
            $request->validate([
                'photos' => 'required|array|max:20',
                'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
                'upload_type' => 'required|in:property,roomType',
                'type_id' => 'required|integer'
            ]);

            $uploadType = $request->upload_type;
            $typeId = $request->type_id;

            $this->authorizeTarget($uploadType, $typeId);

            $path = "/{$uploadType}_{$typeId}/";

            // get current max sort_order
            $maxOrder = DB::table('photos')
                ->where('upload_type', $uploadType)
                ->where('type_id', $typeId)
                ->max('sort_order') ?? 0;

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
                        'updated_at' => now()
                    ]);
                }
            return response()->json(['status' => 'success']);
        }


        public function updatePhotoOrder(Request $request)
        {
            $photos = $request->input('photos');

            if (!$photos || !is_array($photos)) {
                return response()->json(['error' => 'Invalid data'], 400);
            }

            // Extract IDs
            $ids = collect($photos)->pluck('id')->toArray();

            // 🔒 SECURITY: ensure all photos belong to same group
            $firstPhoto = DB::table('photos')->where('id', $ids[0])->first();

            if (!$firstPhoto) {
                return response()->json(['error' => 'Invalid photo'], 404);
            }

            $uploadType = $firstPhoto->upload_type;
            $typeId = $firstPhoto->type_id;

            $this->authorizeTarget($uploadType, $typeId);

            // Validate all IDs belong to same group
            $validCount = DB::table('photos')
                ->whereIn('id', $ids)
                ->where('upload_type', $uploadType)
                ->where('type_id', $typeId)
                ->count();

            if ($validCount !== count($ids)) {
                return response()->json(['error' => 'Invalid photo set'], 403);
            }

            // ✅ UPDATE (loop version - safe)
            foreach ($photos as $photo) {
                DB::table('photos')
                    ->where('id', $photo['id'])
                    ->update(['sort_order' => $photo['sort_order']]);
            }

            return response()->json(['status' => 'success']);
        }

        // Photos are hidden (deactivated), never deleted; the files stay in storage
        public function deactivatePhoto($photo_id)
        {
            // 🔍 Find photo
            $photo = DB::table('photos')->where('id', $photo_id)->first();

            if (!$photo) {
                return response()->json(['error' => 'Photo not found'], 404);
            }

            // 🔒 Ownership check
            $this->authorizeTarget($photo->upload_type, $photo->type_id);

            DB::table('photos')->where('id', $photo_id)->update(['is_active' => false, 'updated_at' => now()]);
            \App\Support\Audit::log('photo.removed', null, "Photo #$photo_id ({$photo->upload_type} #{$photo->type_id}) removed from the gallery", null,
                $photo->upload_type === 'property' ? (int) $photo->type_id : null);

            // 🔄 Reorder remaining active photos
            $remaining = DB::table('photos')
                ->where('upload_type', $photo->upload_type)
                ->where('type_id', $photo->type_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($remaining as $index => $item) {
                DB::table('photos')
                    ->where('id', $item->id)
                    ->update(['sort_order' => $index + 1]);
            }

            return response()->json(['status' => 'success']);
        }
}

