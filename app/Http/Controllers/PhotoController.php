<?php

namespace App\Http\Controllers;
use App\Models\Amenity;
use App\Models\PropertyAmenityXref;
use App\Models\RoomAmenityXref;
use App\Models\RoomType;
use App\Models\Property;
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
        $roomType = (isset(request()->roomType_id))?RoomType::find(request()->roomType_id):null;
        return view('photo.managePhotos',compact('property','roomType'));
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

        // 🔥 OPTIMIZE IMAGE
        $image = Image::make($file)
            ->resize(1600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('webp', 80);

        Storage::put('public'.$path.$filename, $image);

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
}
