<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GuestApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only REST API with the same answers the chatbot and the MCP
 * server give (App\Services\GuestApi). Prices default to LKR; pass
 * ?currency=USD for foreign rates.
 */
class GuestApiController extends Controller
{
    public function __construct(private GuestApi $api)
    {
    }

    public function destinations(): JsonResponse
    {
        return response()->json(['destinations' => $this->api->destinations()]);
    }

    // GET /api/v1/properties?check_in=&check_out=&area=&guests_per_room=&rooms=&currency=
    public function search(Request $request): JsonResponse
    {
        return $this->respond($this->api->searchProperties(
            $request->query('check_in'), $request->query('check_out'), $request->query('area'),
            $request->filled('guests_per_room') ? (int) $request->query('guests_per_room') : null,
            (int) $request->query('rooms', 1), (string) $request->query('currency', 'LKR'),
        ));
    }

    public function property(Request $request, int $property_id): JsonResponse
    {
        return $this->respond($this->api->property($property_id, $request->query('check_in')), 404);
    }

    // GET /api/v1/properties/{id}/availability?check_in=&check_out=&guests_per_room=&rooms=&currency=
    public function availability(Request $request, int $property_id): JsonResponse
    {
        return $this->respond($this->api->availability($property_id,
            $request->query('check_in'), $request->query('check_out'),
            $request->filled('guests_per_room') ? (int) $request->query('guests_per_room') : null,
            (int) $request->query('rooms', 1), (string) $request->query('currency', 'LKR'),
        ));
    }

    public function cancellationPolicy(Request $request, int $property_id): JsonResponse
    {
        return $this->respond($this->api->cancellationPolicy($property_id, $request->query('check_in')), 404);
    }

    // POST /api/v1/bookings/lookup {reference, email} - POST so the email is not logged in URLs
    public function findBooking(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email'],
        ]);

        return $this->respond($this->api->findBooking($data['reference'], $data['email']), 404);
    }

    private function respond(array $result, int $errorStatus = 422): JsonResponse
    {
        return response()->json($result, isset($result['error']) ? $errorStatus : 200);
    }
}
