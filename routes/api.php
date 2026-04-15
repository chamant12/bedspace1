<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertyController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/get-districts-by-province/{province_id}', [PropertyController::class, 'getDistrictsByProvince']);
Route::get('/get-cities-by-district/{district_id}', [PropertyController::class, 'getCitiesByDistrict']);
