<?php

use App\Http\Controllers\Api\V1\GuestApiController;
use App\Http\Controllers\McpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The AJAX endpoints used by the Blade pages live in routes/web.php (under the
// /api prefix) so they share the session login and CSRF protection.

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public read-only guest API (same data as the chatbot and the MCP server)
Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('/destinations', [GuestApiController::class, 'destinations']);
    Route::get('/properties', [GuestApiController::class, 'search']);
    Route::get('/properties/{property_id}', [GuestApiController::class, 'property'])->whereNumber('property_id');
    Route::get('/properties/{property_id}/availability', [GuestApiController::class, 'availability'])->whereNumber('property_id');
    Route::get('/properties/{property_id}/cancellation-policy', [GuestApiController::class, 'cancellationPolicy'])->whereNumber('property_id');
    Route::post('/bookings/lookup', [GuestApiController::class, 'findBooking'])->middleware('throttle:10,1');
});

// MCP server (Streamable HTTP, stateless JSON): POST /api/mcp
Route::post('/mcp', [McpController::class, 'handle'])->middleware('throttle:120,1');
Route::match(['get', 'delete'], '/mcp', [McpController::class, 'notAllowed']);
