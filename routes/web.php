<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin;

// Public (guest-facing) pages
Route::middleware('visitor.currency')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});


Route::get('/login', [UserLoginController::class, 'login'])->name('login');
Route::post('/logout', [UserLoginController::class, 'logout'])->middleware('auth');
Route::post('login', [UserLoginController::class, 'authenticateUser']);
Route::get('/signup', [UserLoginController::class, 'propertyOwnerSignup']);
Route::post('/create-property-owner', [UserLoginController::class, 'createPropertyOwner']);

Route::get('/admin/login', [UserLoginController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', [UserLoginController::class, 'authenticateAdmin']);

// Platform admin area
Route::prefix('admin')->middleware(['auth', 'user.type:admin'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index']);
    Route::get('/properties', [Admin\PropertyController::class, 'index']);
    Route::post('/properties/{property_id}/toggle-active', [Admin\PropertyController::class, 'toggleActive']);
    Route::get('/owners', [Admin\OwnerController::class, 'index']);
    Route::post('/owners/{user_id}/toggle-status', [Admin\OwnerController::class, 'toggleStatus']);
    Route::get('/amenities', [Admin\AmenityController::class, 'index']);
    Route::post('/amenities', [Admin\AmenityController::class, 'store']);
    Route::put('/amenities/{amenity_id}', [Admin\AmenityController::class, 'update']);
    Route::post('/amenities/{amenity_id}/toggle-active', [Admin\AmenityController::class, 'toggleActive']);
});

// Property owner area
Route::middleware(['auth', 'user.type:owner'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/my-properties', [PropertyController::class, 'listMyProperties']);
    Route::get('/add-property', [PropertyController::class, 'addProperty']);
    Route::post('/create-property', [PropertyController::class, 'createProperty']);
    Route::get('/view-property/{property_id}', [PropertyController::class, 'viewProperty']);
    Route::post('/update-property', [PropertyController::class, 'updateProperty']);
    Route::post('/property/{property_id}/toggle-active', [PropertyController::class, 'togglePropertyActive']);
    Route::get('/add-room-type', [RoomTypeController::class, 'addRoomType']);
    Route::post('/create-room-type', [RoomTypeController::class, 'createRoomType']);
    Route::post('/roomType/{roomType_id}/toggle-active', [RoomTypeController::class, 'toggleRoomTypeActive']);
    Route::get('/view-roomType/{roomType_id}', [RoomTypeController::class, 'viewRoomType']);
    Route::post('/update-room-type', [RoomTypeController::class, 'updateRoomType']);
    Route::get('/add-base-rate/{property_id}', [RateController::class, 'addBaseRate']);
    Route::post('/update-exchange-rates', [RateController::class, 'updateExchangeRates']);
    Route::get('/exchange-rates', [RateController::class, 'editExchangeRates']);
    Route::get('/amenities', [AmenityController::class, 'amenities']);
    Route::post('/addAmenity', [AmenityController::class, 'addAmenity']);
    Route::get('/photos', [PhotoController::class, 'selectPhotoType']);
    Route::post('/manage-photos', [PhotoController::class, 'managePhotos']);

    // AJAX endpoints used by the Blade pages
    Route::prefix('api')->group(function () {
        Route::get('/get-districts-by-province/{province_id}', [PropertyController::class, 'getDistrictsByProvince']);
        Route::get('/get-cities-by-district/{district_id}', [PropertyController::class, 'getCitiesByDistrict']);
        Route::get('/get-properties-by-city/{city_id}', [RoomTypeController::class, 'getPropertiesByCity']);
        Route::get('/get-base-rates/{roomType_id}/{currency_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRates']);
        Route::post('/update-baserate', [RateController::class, 'updateBaseRates']);
        Route::post('/remove-amenity/{amenity_id}/{type_id}/{amenityType}', [AmenityController::class, 'removeAmenity']);
        Route::get('/get-amenities/{amenity_type}/{type_id}', [AmenityController::class, 'getAmenities']);
        Route::post('/upload-photos', [PhotoController::class, 'uploadPhotos']);
        Route::post('/update-photo-order', [PhotoController::class, 'updatePhotoOrder']);
        Route::post('/remove-photo/{photo_id}', [PhotoController::class, 'deactivatePhoto']);
    });
});
