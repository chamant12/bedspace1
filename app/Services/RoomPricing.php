<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\CurrencyType;
use App\Models\Property;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Prices stays from the rates calendar (see RatesCalendar for the rules).
 *
 * A room type can be booked at a property for a stay only if the chosen
 * occupancy + meal plan combination is complete (USD and LKR) on every
 * night. The guest is charged in their currency; prices are never
 * converted between currencies.
 */
class RoomPricing
{
    public function __construct(private RatesCalendar $calendar)
    {
    }

    /**
     * Returns null if the combination is not complete on every night, otherwise:
     * ['currency' => CurrencyType, 'nights' => [Y-m-d => amount], 'total' => float]
     */
    public function stayPrice(Property $property, RoomType $roomType, int $occupancyId, int $mealTypeId,
                              CarbonInterface $checkIn, CarbonInterface $checkOut, CurrencyType $visitorCurrency): ?array
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return null;
        }

        $currencyIds = CurrencyType::whereIn('code', RatesCalendar::CURRENCIES)->pluck('id');

        // [date][currency_type_id] => rate
        $rates = [];
        $this->baseRates($property)
            ->where('roomType_id', $roomType->id)
            ->where('occupancy_id', $occupancyId)
            ->where('mealType_id', $mealTypeId)
            ->whereIn('currency_type_id', $currencyIds)
            ->whereIn('rate_for', $dates)
            ->get(['rate_for', 'currency_type_id', 'rate'])
            ->each(function ($row) use (&$rates) {
                $rates[substr($row->rate_for, 0, 10)][$row->currency_type_id] = (float) $row->rate;
            });

        $nights = [];
        foreach ($dates as $date) {
            $complete = count(array_intersect_key($rates[$date] ?? [], $currencyIds->flip()->all())) === $currencyIds->count();
            if (!$complete || !isset($rates[$date][$visitorCurrency->id])) {
                return null;
            }
            $nights[$date] = $rates[$date][$visitorCurrency->id];
        }

        return [
            'currency' => $visitorCurrency,
            'nights' => $nights,
            'total' => round(array_sum($nights), 2),
        ];
    }

    /**
     * Search visibility: true if at least one of the owner's active room types
     * has one combination that is complete (USD + LKR) on every night.
     */
    public function isPriceable(Property $property, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return false;
        }

        $currencyIds = CurrencyType::whereIn('code', RatesCalendar::CURRENCIES)->pluck('id');

        // Nights per combination on which every currency has a price
        $completeNights = $this->baseRates($property)
            ->whereIn('currency_type_id', $currencyIds)
            ->whereIn('roomType_id', RoomType::where('is_active', true)->select('id'))
            ->whereIn('rate_for', $dates)
            ->selectRaw('roomType_id, occupancy_id, mealType_id, rate_for')
            ->groupBy('roomType_id', 'occupancy_id', 'mealType_id', 'rate_for')
            ->havingRaw('COUNT(DISTINCT currency_type_id) = ?', [$currencyIds->count()])
            ->get()
            ->groupBy(fn ($row) => $row->roomType_id.'|'.$row->occupancy_id.'|'.$row->mealType_id);

        return $completeNights->contains(fn ($nights) => $nights->count() === count($dates));
    }

    // Y-m-d of every night from check-in up to (not including) check-out
    private function nights(CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        if ($checkOut->lte($checkIn)) {
            return [];
        }

        return collect(CarbonPeriod::create($checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()))
            ->map(fn ($date) => $date->toDateString())
            ->all();
    }

    // Prices set for this property (rates are keyed by the property's base rate type)
    private function baseRates(Property $property)
    {
        return BaseRate::query()
            ->where('rate', '>', 0)
            ->where('rateType_id', $this->calendar->rateTypeId($property));
    }
}
