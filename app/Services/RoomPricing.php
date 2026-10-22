<?php

namespace App\Services;

use App\Models\BaseRate;
use App\Models\CurrencyType;
use App\Models\Property;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Prices a stay from the owner's base rates.
 *
 * Owners keep separate rates per currency (LKR for local guests, USD for
 * foreign guests). The visitor's cookie decides which price list applies
 * (the "rate currency"). For a night with no rate in that currency, the
 * rate in the other currency is converted with the owner's latest exchange
 * rate. A night with no rate in any currency cannot be priced, and a
 * property with no priceable room type is hidden from search results.
 *
 * A rate of 0 counts as "not set" (the rate grid shows 0 for empty cells).
 */
class RoomPricing
{
    public function __construct(private ExchangeRates $exchangeRates)
    {
    }

    /**
     * Returns null if any night cannot be priced, otherwise:
     * ['currency' => CurrencyType, 'nights' => [Y-m-d => amount], 'total' => float,
     *  'converted_nights' => [Y-m-d, ...]]  (nights priced by conversion)
     */
    public function stayPrice(RoomType $roomType, int $occupancyId, int $mealTypeId,
                              CarbonInterface $checkIn, CarbonInterface $checkOut, CurrencyType $rateCurrency): ?array
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return null;
        }

        $ownerId = (int) $roomType->property->property_owner_id;
        $currencies = CurrencyType::all()->keyBy('id');

        // [date][currency_type_id] => rate
        $rates = [];
        $this->baseRates()
            ->where('roomType_id', $roomType->id)
            ->where('occupancy_id', $occupancyId)
            ->where('mealType_id', $mealTypeId)
            ->whereIn('rate_for', $dates)
            ->get(['rate_for', 'currency_type_id', 'rate'])
            ->each(function ($row) use (&$rates) {
                $rates[substr($row->rate_for, 0, 10)][$row->currency_type_id] = (float) $row->rate;
            });

        $nights = [];
        $converted = [];
        foreach ($dates as $date) {
            $available = $rates[$date] ?? [];

            if (isset($available[$rateCurrency->id])) {
                $nights[$date] = $available[$rateCurrency->id];
                continue;
            }

            // Prefer converting from the base currency (LKR), then any other
            uksort($available, fn ($a, $b) => ($currencies[$b]->is_base ?? false) <=> ($currencies[$a]->is_base ?? false));
            $amount = null;
            foreach ($available as $currencyId => $rate) {
                $amount = $this->exchangeRates->convert($rate, $currencies[$currencyId], $rateCurrency, $ownerId);
                if ($amount !== null) {
                    break;
                }
            }

            if ($amount === null) {
                return null;
            }
            $nights[$date] = $amount;
            $converted[] = $date;
        }

        return [
            'currency' => $rateCurrency,
            'nights' => $nights,
            'total' => round(array_sum($nights), 2),
            'converted_nights' => $converted,
        ];
    }

    // Converts a price for display when the visitor switched currency
    public function forDisplay(float $amount, CurrencyType $rateCurrency, CurrencyType $displayCurrency, Property $property): ?float
    {
        return $this->exchangeRates->convert($amount, $rateCurrency, $displayCurrency, (int) $property->property_owner_id);
    }

    /**
     * Search visibility: true if at least one active room type has a rate
     * (in any currency) for every night, for some occupancy and meal plan.
     */
    public function isPriceable(Property $property, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        $dates = $this->nights($checkIn, $checkOut);
        if (!$dates) {
            return false;
        }

        $nightsPriced = $this->baseRates()
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
