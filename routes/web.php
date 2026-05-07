<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\AmenityController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', [UserLoginController::class, 'login'])->name('login');
Route::get('/logout', [UserLoginController::class, 'logout']);
Route::post('login', [UserLoginController::class, 'authenticateUser']);
Route::get('/signup', [UserLoginController::class, 'propertyOwnerSignup']);
Route::post('/create-property-owner', [UserLoginController::class, 'createPropertyOwner']);

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
            return view('dashboard');
    });
    Route::get('/my-properties', [PropertyController::class, 'listMyProperties']);
    Route::get('/add-property', [PropertyController::class, 'addProperty']);
    Route::post('/create-property', [PropertyController::class, 'createProperty']);
    Route::get('/view-property/{property_id}', [PropertyController::class, 'viewProperty']);
    Route::post('/update-property', [PropertyController::class, 'updateProperty']);
    Route::get('/add-room-type', [RoomTypeController::class, 'addRoomType']);
    Route::post('/create-room-type', [RoomTypeController::class, 'createRoomType']);
    Route::get('/delete-roomType/{roomType_id}', [RoomTypeController::class, 'deleteRoomType']);
    Route::get('/view-roomType/{roomType_id}', [RoomTypeController::class, 'viewRoomType']);
    Route::post('/update-room-type', [RoomTypeController::class, 'updateRoomType']);
    Route::get('/add-base-rate/{property_id}', [RateController::class, 'addBaseRate']);
    Route::post('/update-doillar-rate', [RateController::class, 'updateDollarRate']);
    Route::get('/edit-dollar-rate', [RateController::class, 'editDollarRate']);
    Route::get('/amenities', [AmenityController::class, 'amenities']);
    Route::post('/addAmenity', [AmenityController::class, 'addAmenity']);
    
});