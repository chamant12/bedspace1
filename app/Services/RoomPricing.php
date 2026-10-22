<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\CurrencyType;
use App\Models\Property;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Prices a stay from the owner's rates calendar.
 *
 * Every offered combination (date x occupancy x meal plan) has a USD price,
 * which is compulsory, and usually an LKR price. A stay is priced in the
 * visitor's currency; if any night has no price in that currency the whole
 * stay is priced in USD instead. Prices are never converted between
 * currencies (exchange rates are only for income reports).
 *
 * A rate of 0 means "not offered".
 */
class RoomPricing
{
    /**
     * Returns null if the combination is not offered for every night, otherwise:
     * ['currency' => CurrencyType, 'nights' => [Y-m-d => amount], 'total' => float]
     */
    public function stayPrice(RoomType $roomType, int $occupancyId, int $mealTypeId,
                              CarbonInterface $checkIn, CarbonInterface $checkOut, CurrencyType $visitorCurrency): ?array
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return null;
        }

        $usd = CurrencyType::byCode(CurrencyType::USD);

        // [currency_type_id][date] => rate
        $rates = [];
        $this->baseRates()
            ->where('roomType_id', $roomType->id)
            ->where('occupancy_id', $occupancyId)
            ->where('mealType_id', $mealTypeId)
            ->whereIn('currency_type_id', array_unique([$visitorCurrency->id, $usd->id]))
            ->whereIn('rate_for', $dates)
            ->get(['rate_for', 'currency_type_id', 'rate'])
            ->each(function ($row) use (&$rates) {
                $rates[$row->currency_type_id][substr($row->rate_for, 0, 10)] = (float) $row->rate;
            });

        foreach ([$visitorCurrency, $usd] as $currency) {
            $nights = array_intersect_key($rates[$currency->id] ?? [], array_flip($dates));
            if (count($nights) === count($dates)) {
                ksort($nights);
                return [
                    'currency' => $currency,
                    'nights' => $nights,
                    'total' => round(array_sum($nights), 2),
                ];
            }
        }

        return null;
    }

    /**
     * Search visibility: true if at least one active room type has a USD price
     * for every night, for some occupancy and meal plan.
     */
    public function isPriceable(Property $property, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return false;
        }

        $nightsPriced = $this->baseRates()
            ->where('currency_type_id', CurrencyType::byCode(CurrencyType::USD)->id)
            ->whereIn('roomType_id', $property->roomTypes()->where('is_active', true)->select('id'))
            ->whereIn('rate_for', $dates)
            ->selectRaw('roomType_id, occupancy_id, mealType_id, COUNT(DISTINCT rate_for) as nights')
            ->groupBy('roomType_id', 'occupancy_id', 'mealType_id')
            ->get();

        return $nightsPriced->contains(fn ($row) => (int) $row->nights === count($dates));
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

    private function baseRates()
    {
        return BaseRate::query()
            ->where('rate', '>', 0)
            ->whereIn('rateType_id', function ($query) {
                $query->select('id')->from('ratetypes')->where('rateType', 'base_rate');
            });
    }
}
