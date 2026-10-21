<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The AJAX endpoints used by the Blade pages live in routes/web.php (under the
// /api prefix) so they share the session login and CSRF protection.

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
