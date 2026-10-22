<?php

namespace App\Services;

use App\Models\CurrencyType;
use App\Models\MealType;
use App\Models\Occupancy;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\RoomType;
use App\Support\BusinessDate;
use Carbon\CarbonImmutable;

/**
 * Prices and availability for guests: what can be booked at a property for
 * a stay, and the exact price of one choice (rates calendar + promotions).
 */
class BookingQuotes
{
    const MAX_NIGHTS = 30;
    const MAX_ROOMS = 10;

    public function __construct(
        private RoomPricing $pricing,
        private Inventory $inventory,
        private PromotionEngine $promotions,
    ) {
    }

    // Error message if the stay dates are not bookable, otherwise null
    public function validateStay(string $checkIn, string $checkOut): ?string
    {
        if (!$this->isDate($checkIn) || !$this->isDate($checkOut)) {
            return 'Please choose valid dates.';
        }
        if ($checkIn < BusinessDate::today()) {
            return 'Check-in cannot be in the past.';
        }
        if ($checkOut <= $checkIn) {
            return 'Check-out must be after check-in.';
        }
        if (CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut)) > self::MAX_NIGHTS) {
            return 'Stays can be booked for up to '.self::MAX_NIGHTS.' nights.';
        }
        return null;
    }

    /**
     * Price of $rooms rooms of one combination, or null if not bookable.
     * ['nightly' => [date => price per room], 'nights', 'rooms', 'available',
     *  'subtotal', 'discounts' => [...], 'discount_total', 'total', 'currency']
     */
    public function quote(Property $property, RoomType $roomType, int $occupancyId, int $mealTypeId,
                          string $checkIn, string $checkOut, int $rooms, CurrencyType $currency): ?array
    {
        if ($this->validateStay($checkIn, $checkOut) || $rooms < 1 || $rooms > self::MAX_ROOMS) {
            return null;
        }

        $in = CarbonImmutable::parse($checkIn);
        $out = CarbonImmutable::parse($checkOut);

        $price = $this->pricing->stayPrice($property, $roomType, $occupancyId, $mealTypeId, $in, $out, $currency);
        if (!$price) {
            return null;
        }

        $available = $this->inventory->availableForStay($property, $roomType, $checkIn, $checkOut);
        if ($available < $rooms) {
            return null;
        }

        $subtotal = round($price['total'] * $rooms, 2);
        $discounts = $this->promotions->discountsFor($property, $roomType, CarbonImmutable::now(config('bedspace.timezone')),
            $in, $out, $currency, $subtotal);
        $discountTotal = round(array_sum(array_column($discounts, 'amount')), 2);

        return [
            'nightly' => $price['nights'],
            'nights' => count($price['nights']),
            'rooms' => $rooms,
            'available' => $available,
            'subtotal' => $subtotal,
            'discounts' => $discounts,
            'discount_total' => $discountTotal,
            'total' => round($subtotal - $discountTotal, 2),
            'currency' => $currency,
        ];
    }

    /**
     * Everything bookable at a property for a stay, for the property page:
     * [ ['roomType', 'available', 'options' => [ ['occupancy', 'mealType', 'quote'] ]] ]
     * Optionally limited to one occupancy (guests per room).
     */
    public function offers(Property $property, string $checkIn, string $checkOut, CurrencyType $currency, ?int $guestsPerRoom = null, int $rooms = 1): array
    {
        if (!$property->is_active || $this->validateStay($checkIn, $checkOut)) {
            return [];
        }

        $roomTypes = RoomType::where('is_active', true)
            ->whereIn('id', PropertyRoomType::where(['property_id' => $property->id, 'is_active' => true])->where('total_rooms', '>', 0)->select('roomType_id'))
            ->orderBy('roomType')
            ->get();
        $mealTypes = MealType::orderBy('id')->get();

        $offers = [];
        foreach ($roomTypes as $roomType) {
            $available = $this->inventory->availableForStay($property, $roomType, $checkIn, $checkOut);
            if ($available < $rooms) {
                continue;
            }

            $occupancies = Occupancy::where('occupancy', '<=', $roomType->max_occupancy)
                ->when($guestsPerRoom, fn ($q) => $q->where('occupancy', $guestsPerRoom))
                ->orderBy('occupancy')
                ->get();

            $options = [];
            foreach ($occupancies as $occupancy) {
                foreach ($mealTypes as $mealType) {
                    $quote = $this->quote($property, $roomType, $occupancy->id, $mealType->id, $checkIn, $checkOut, $rooms, $currency);
                    if ($quote) {
                        $options[] = ['occupancy' => $occupancy, 'mealType' => $mealType, 'quote' => $quote];
                    }
                }
            }

            if ($options) {
                $offers[] = ['roomType' => $roomType, 'available' => $available, 'options' => $options];
            }
        }

        return $offers;
    }

    // Lowest total for the search results list, or null if nothing is bookable
    public function lowestPrice(Property $property, string $checkIn, string $checkOut, CurrencyType $currency, ?int $guestsPerRoom, int $rooms): ?float
    {
        $totals = [];
        foreach ($this->offers($property, $checkIn, $checkOut, $currency, $guestsPerRoom, $rooms) as $offer) {
            foreach ($offer['options'] as $option) {
                $totals[] = $option['quote']['total'];
            }
        }
        return $totals ? min($totals) : null;
    }

    private function isDate($date): bool
    {
        return is_string($date)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
    }
}
