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
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PhotoController extends Controller
{
    public function selectPhotoType(){
        $properties = Property::with('city','city.district')->where(['property_owner_id'=>auth()->user()->id])->get();
        return view('photo.phototype',compact('properties'));
    }

    public function managePhotos(){
        $property = Property::find(request()->property_id);
        $upload_type = "property";
        $type_id = Request()->property_id;
        $roomType = (isset(request()->roomType_id))?RoomType::find(request()->roomType_id):null;
        if($roomType!=null){
            $upload_type = "roomType";
            $type_id = Request()->roomType_id;
        }
        $photos = Photo::where(['upload_type'=>$upload_type,'type_id'=>$type_id])->orderBy('sort_order')->get();
        return view('photo.managePhotos',compact('property','roomType','photos'));
    }


        public function uploadPhotos(Request $request)
        {
            $request->validate([
                'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
                'upload_type' => 'required|in:property,roomType',
                'type_id' => 'required|integer'
            ]);

            $uploadType = $request->upload_type;
            $typeId = $request->type_id;

            $path = "/{$uploadType}_{$typeId}/";

            // get current max sort_order
            $maxOrder = DB::table('photos')
                ->where('upload_type', $uploadType)
                ->where('type_id', $typeId)
                ->max('sort_order') ?? 0;

                    foreach ($request->file('photos') as $index => $file) {

                    $filename = uniqid().'.webp';
                    $fullPath = storage_path('app/public'.$path.$filename);

                    $fullDir = storage_path('app/public'.$path);

                    if (!file_exists($fullDir)) {
                        mkdir($fullDir, 0775, true);
                    }

                    Image::make($file)
                        ->resize(1600, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->encode('webp', 80)
                        ->save($fullPath);

                    DB::table('photos')->insert([
                        'upload_path' => $path,
                        'upload_type' => $uploadType,
                        'type_id' => $typeId,
                        'file_name' => $filename,
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

        public function deletePhoto($photo_id)
        {
            // 🔍 Find photo
            $photo = DB::table('photos')->where('id', $photo_id)->first();

            if (!$photo) {
                return response()->json(['error' => 'Photo not found'], 404);
            }

            // 🔒 (IMPORTANT) Optional: validate ownership here
            // Example:
            // if ($photo->type_id != auth()->user()->property_id) { return 403; }

            // 📁 Build file path
            $filePath = 'public' . $photo->upload_path . $photo->file_name;

            // 🗑 Delete file from storage
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            // ❌ Delete DB record
            DB::table('photos')->where('id', $photo_id)->delete();

            // 🔄 Reorder remaining photos (important!)
            $remaining = DB::table('photos')
                ->where('upload_type', $photo->upload_type)
                ->where('type_id', $photo->type_id)
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

