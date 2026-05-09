<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RateTypeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\PhotoController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/get-districts-by-province/{province_id}', [PropertyController::class, 'getDistrictsByProvince']);
Route::get('/get-cities-by-district/{district_id}', [PropertyController::class, 'getCitiesByDistrict']);
Route::get('/get-properties-by-city/{city_id}/{property_owner_id}', [RoomTypeController::class, 'getPropertiesByCity']);
Route::get('/get-base-rates/{roomType_id}/{currency_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRates']);
Route::post('/update-baserate', [RateController::class, 'updateBaseRates']);
Route::get('/deleteAmenity/{amenity_id}/{type_id}/{amenityType}', [AmenityController::class, 'deleteAmenity']);
Route::get('/get-amenities/{amenity_type}/{type_id}', [AmenityController::class, 'getAmenities']);
Route::post('/addAmenity', [AmenityController::class, 'addAmenity']);
Route::post('/upload-photos', [PhotoController::class, 'uploadPhotos']);







