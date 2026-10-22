<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RoomNumberController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Admin;

// Public (guest-facing) pages
Route::middleware('visitor.currency')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
    Route::post('/currency', [CurrencyController::class, 'switchCurrency']);

    // Booking engine
    Route::get('/search', [PublicBookingController::class, 'search']);
    Route::get('/stay/{property_id}', [PublicBookingController::class, 'property']);
    Route::get('/book', [PublicBookingController::class, 'checkout']);
    Route::post('/book', [PublicBookingController::class, 'book'])->middleware('throttle:10,1');
    Route::get('/booking/find', [PublicBookingController::class, 'findBookingForm']);
    Route::post('/booking/find', [PublicBookingController::class, 'findBooking'])->middleware('throttle:10,1');
    Route::get('/booking/{reference}', [PublicBookingController::class, 'booking'])->name('booking.show');

    // Optional guest accounts
    Route::get('/account/login', [UserLoginController::class, 'customerLogin']);
    Route::post('/account/login', [UserLoginController::class, 'authenticateCustomer'])->middleware('throttle:10,1');
    Route::get('/account/bookings', [PublicBookingController::class, 'myBookings'])->middleware(['auth', 'user.type:customer']);
});


Route::get('/login', [UserLoginController::class, 'login'])->name('login');
Route::post('/logout', [UserLoginController::class, 'logout'])->middleware('auth');
Route::post('login', [UserLoginController::class, 'authenticateUser'])->middleware('throttle:10,1');
Route::get('/signup', [UserLoginController::class, 'propertyOwnerSignup']);
Route::post('/create-property-owner', [UserLoginController::class, 'createPropertyOwner']);

Route::get('/admin/login', [UserLoginController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', [UserLoginController::class, 'authenticateAdmin'])->middleware('throttle:10,1');

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

// Pages shared by several user types; access is decided by policies (admin passes all)
Route::middleware('auth')->group(function () {
    Route::get('/rates/{property_id}/view', [RateController::class, 'viewRates']);
    Route::get('/api/view-base-rates/{property_id}/{roomType_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRatesReadonly']);
    Route::get('/inventory/{property_id}/view', [InventoryController::class, 'view']);
    Route::get('/api/view-inventory/{property_id}/{roomType_id}/{from_date}/{to_date}', [InventoryController::class, 'gridReadonly']);
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
    Route::get('/inventory/{property_id}', [InventoryController::class, 'index']);
    Route::get('/rooms/{property_id}', [RoomNumberController::class, 'index']);
    Route::post('/rooms', [RoomNumberController::class, 'store']);
    Route::put('/rooms/{room_id}', [RoomNumberController::class, 'update']);
    Route::post('/rooms/{room_id}/toggle-active', [RoomNumberController::class, 'toggleActive']);
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
        Route::get('/get-base-rates/{property_id}/{roomType_id}/{from_date}/{to_date}', [RateController::class, 'getBaseRates']);
        Route::post('/save-base-rates', [RateController::class, 'saveBaseRates']);
        Route::post('/reset-base-rates', [RateController::class, 'resetBaseRates']);
        Route::post('/copy-base-rates', [RateController::class, 'copyBaseRates']);
        Route::get('/inventory/{property_id}/{roomType_id}/{from_date}/{to_date}', [InventoryController::class, 'grid']);
        Route::post('/inventory/total', [InventoryController::class, 'saveTotal']);
        Route::post('/inventory/closed', [InventoryController::class, 'saveClosed']);
        Route::post('/inventory/close-range', [InventoryController::class, 'closeRange']);
        Route::post('/remove-amenity/{amenity_id}/{type_id}/{amenityType}', [AmenityController::class, 'removeAmenity']);
        Route::get('/get-amenities/{amenity_type}/{type_id}', [AmenityController::class, 'getAmenities']);
        Route::post('/upload-photos', [PhotoController::class, 'uploadPhotos']);
        Route::post('/update-photo-order', [PhotoController::class, 'updatePhotoOrder']);
        Route::post('/remove-photo/{photo_id}', [PhotoController::class, 'deactivatePhoto']);
    });
});
