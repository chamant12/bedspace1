<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\PropertyController;

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
});