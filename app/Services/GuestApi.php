<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Amenity;
use App\Models\CurrencyType;
use App\Models\District;
use App\Models\Photo;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

/**
 * Read-only, guest-facing information about Bedspace properties, shared by
 * the public REST API (/api/v1), the MCP server (/mcp) and the chatbot.
 *
 * Privacy (NFR-03): only public property information is returned, never
 * owner or financial data. A booking is only returned when both its
 * reference and the guest's email match. Every method returns plain
 * arrays; problems come back as ['error' => message], not exceptions.
 */
class GuestApi
{
    const MAX_RESULTS = 10;

    public function __construct(
        private BookingQuotes $quotes,
        private CancellationPolicy $cancellationPolicy,
    ) {
    }

    // Areas (districts) that have bookable properties
    public function destinations(): array
    {
        return District::with('province')
            ->whereIn('id', fn ($q) => $q->select('cities.district_id')->from('properties')
                ->join('cities', 'cities.id', '=', 'properties.city_id')
                ->where('properties.is_active', true))
            ->orderBy('district_name')
            ->get()
            ->map(fn ($district) => [
                'district_id' => $district->id,
                'district' => $district->district_name,
                'province' => $district->province?->province_name,
            ])->all();
    }

    /**
     * Properties bookable for the stay, cheapest first.
     * $area: district id or (part of) a district / city name.
     */
    public function searchProperties(?string $checkIn, ?string $checkOut, ?string $area, ?int $guestsPerRoom, int $rooms, string $currencyCode): array
    {
        [$checkIn, $checkOut] = $this->defaultDates($checkIn, $checkOut);
        if ($error = $this->quotes->validateStay($checkIn, $checkOut)) {
            return ['error' => $error];
        }
        $currency = $this->currency($currencyCode);
        $rooms = max(1, min(BookingQuotes::MAX_ROOMS, $rooms));

        $properties = Property::with('city.district')
            ->where('is_active', true)
            ->when($area, function ($q) use ($area) {
                $q->whereIn('city_id', fn ($sub) => $sub->select('cities.id')->from('cities')
                    ->join('districts', 'districts.id', '=', 'cities.district_id')
                    ->when(ctype_digit($area),
                        fn ($w) => $w->where('districts.id', (int) $area),
                        fn ($w) => $w->where('districts.district_name', 'like', '%'.$area.'%')->orWhere('cities.city_name', 'like', '%'.$area.'%')));
            })
            ->get();

        $results = [];
        foreach ($properties as $property) {
            $price = $this->quotes->lowestPrice($property, $checkIn, $checkOut, $currency, $guestsPerRoom, $rooms);
            if ($price !== null) {
                $results[] = [
                    'property_id' => $property->id,
                    'name' => $property->property_name,
                    'city' => $property->city?->city_name,
                    'district' => $property->city?->district?->district_name,
                    'lowest_total_price' => $price,
                    'currency' => $currency->code,
                    'url' => $this->propertyUrl($property, $checkIn, $checkOut, $guestsPerRoom, $rooms),
                ];
            }
        }
        usort($results, fn ($a, $b) => $a['lowest_total_price'] <=> $b['lowest_total_price']);

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'rooms' => $rooms,
            'guests_per_room' => $guestsPerRoom,
            'price_note' => "Prices are totals for the whole stay and all rooms, in {$currency->code}.",
            'properties' => array_slice($results, 0, self::MAX_RESULTS),
            'more_available' => count($results) > self::MAX_RESULTS,
        ];
    }

    // Public description of a property
    public function property(int $propertyId, ?string $checkIn = null): array
    {
        $property = Property::with('city.district.province')->where('is_active', true)->find($propertyId);
        if (!$property) {
            return ['error' => 'Property not found.'];
        }

        $roomTypes = RoomType::where('is_active', true)
            ->whereIn('id', fn ($q) => $q->select('roomType_id')->from('property_room_types')
                ->where(['property_id' => $property->id, 'is_active' => true])->where('total_rooms', '>', 0))
            ->orderBy('roomType')
            ->get()
            ->map(fn ($roomType) => [
                'room_type_id' => $roomType->id,
                'name' => $roomType->roomType,
                'max_guests' => $roomType->max_occupancy,
                'amenities' => $this->amenities('roomType_amenity_xrefs', 'roomType_id', $roomType->id),
            ])->all();

        return [
            'property_id' => $property->id,
            'name' => $property->property_name,
            'address' => $property->address,
            'city' => $property->city?->city_name,
            'district' => $property->city?->district?->district_name,
            'province' => $property->city?->district?->province?->province_name,
            'map_url' => preg_match('#^https?://#i', (string) $property->location) ? $property->location : null,
            'amenities' => $this->amenities('property_amenity_xrefs', 'property_id', $property->id),
            'room_types' => $roomTypes,
            'cancellation_policy' => $checkIn && $this->isDate($checkIn)
                ? $this->cancellationPolicy->statementForStay($property, $checkIn)
                : $this->cancellationPolicy->summary($property),
            'photos' => Photo::where(['upload_type' => 'property', 'type_id' => $property->id, 'is_active' => true])
                ->orderBy('sort_order')->take(5)->get()->map(fn ($photo) => $photo->url)->all(),
            'url' => $this->propertyUrl($property, $checkIn, null, null, 1),
        ];
    }

    // Bookable rooms, meal plans and prices of a property for a stay, with booking links
    public function availability(int $propertyId, ?string $checkIn, ?string $checkOut, ?int $guestsPerRoom, int $rooms, string $currencyCode): array
    {
        $property = Property::where('is_active', true)->find($propertyId);
        if (!$property) {
            return ['error' => 'Property not found.'];
        }
        [$checkIn, $checkOut] = $this->defaultDates($checkIn, $checkOut);
        if ($error = $this->quotes->validateStay($checkIn, $checkOut)) {
            return ['error' => $error];
        }
        $currency = $this->currency($currencyCode);
        $rooms = max(1, min(BookingQuotes::MAX_ROOMS, $rooms));

        $options = [];
        foreach ($this->quotes->offers($property, $checkIn, $checkOut, $currency, $guestsPerRoom, $rooms) as $offer) {
            foreach ($offer['options'] as $option) {
                $quote = $option['quote'];
                $options[] = [
                    'room_type' => $offer['roomType']->roomType,
                    'guests_per_room' => $option['occupancy']->occupancy,
                    'occupancy' => $option['occupancy']->occupancy_name,
                    'meal_plan' => $option['mealType']->mealType,
                    'rooms' => $rooms,
                    'rooms_left' => $offer['available'],
                    'total_price' => $quote['total'],
                    'price_before_discounts' => $quote['subtotal'],
                    'discounts' => array_map(fn ($d) => ['name' => $d['description'], 'amount' => $d['amount']], $quote['discounts']),
                    'currency' => $currency->code,
                    'book_url' => url('/book?'.http_build_query([
                        'property_id' => $property->id,
                        'roomType_id' => $offer['roomType']->id,
                        'occupancy_id' => $option['occupancy']->id,
                        'mealType_id' => $option['mealType']->id,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'rooms' => $rooms,
                    ])),
                ];
            }
        }

        return [
            'property_id' => $property->id,
            'property' => $property->property_name,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut)),
            'available' => (bool) $options,
            'options' => $options,
            'cancellation_policy' => $this->cancellationPolicy->statementForStay($property, $checkIn),
            'property_url' => $this->propertyUrl($property, $checkIn, $checkOut, $guestsPerRoom, $rooms),
        ];
    }

    public function cancellationPolicy(int $propertyId, ?string $checkIn = null): array
    {
        $property = Property::where('is_active', true)->find($propertyId);
        if (!$property) {
            return ['error' => 'Property not found.'];
        }

        return [
            'property' => $property->property_name,
            'no_refund_within_days' => $property->effectiveCancellationDays(),
            'policy' => $checkIn && $this->isDate($checkIn)
                ? $this->cancellationPolicy->statementForStay($property, $checkIn)
                : $this->cancellationPolicy->summary($property),
        ];
    }

    // A guest's own booking: both the reference and the booking email must match
    public function findBooking(string $reference, string $email): array
    {
        $reservation = Reservation::with('property', 'rooms.roomType', 'rooms.mealType', 'rooms.occupancy', 'currency')
            ->where('reference', strtoupper(trim($reference)))->first();

        if (!$reservation || strcasecmp(trim($reservation->guest_email), trim($email)) !== 0) {
            return ['error' => 'No booking was found with that reference and email address.'];
        }

        return [
            'reference' => $reservation->reference,
            'status' => $reservation->status->label(),
            'property' => $reservation->property->property_name,
            'check_in' => $reservation->check_in->toDateString(),
            'check_out' => $reservation->check_out->toDateString(),
            'nights' => $reservation->nights,
            'rooms' => $reservation->rooms->map(fn ($line) => [
                'room_type' => $line->roomType->roomType,
                'rooms' => $line->rooms,
                'occupancy' => $line->occupancy->occupancy_name,
                'meal_plan' => $line->mealType->mealType,
            ])->all(),
            'total' => (float) $reservation->total,
            'currency' => $reservation->currency->code,
            'payment' => $reservation->payment_method->label(),
            'payment_status' => str_replace('_', ' ', $reservation->payment_status),
            'cancellation_policy' => $this->cancellationPolicy->statementForReservation($reservation),
            'can_cancel_online' => in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
                && $reservation->check_in->toDateString() >= BusinessDate::today(),
            // Opens the booking page, where the guest can pay or cancel
            'booking_url' => URL::temporarySignedRoute('booking.show', now()->addDay(), ['reference' => $reservation->reference]),
        ];
    }

    private function amenities(string $table, string $column, int $id): array
    {
        return Amenity::where('is_active', true)
            ->whereIn('id', fn ($q) => $q->select('amenity_id')->from($table)->where([$column => $id, 'is_active' => true]))
            ->orderBy('amenity')->pluck('amenity')->all();
    }

    private function propertyUrl(Property $property, ?string $checkIn, ?string $checkOut, ?int $guests, int $rooms): string
    {
        return url('/stay/'.$property->id.'?'.http_build_query(array_filter([
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'rooms' => $rooms > 1 ? $rooms : null,
        ])));
    }

    // Missing dates: tomorrow for one night
    private function defaultDates(?string $checkIn, ?string $checkOut): array
    {
        $checkIn = $checkIn ?: CarbonImmutable::parse(BusinessDate::today())->addDay()->toDateString();
        if (!$checkOut) {
            // An invalid check-in is reported by validateStay(); don't try to compute from it
            $checkOut = $this->isDate($checkIn) ? CarbonImmutable::parse($checkIn)->addDay()->toDateString() : $checkIn;
        }
        return [$checkIn, $checkOut];
    }

    private function currency(string $code): CurrencyType
    {
        return CurrencyType::active()->where('code', strtoupper($code))->first()
            ?? CurrencyType::byCode(config('bedspace.local_currency'));
    }

    private function isDate(string $date): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) !== false;
    }
}
