<?php

namespace App\Services;

use App\Models\CurrencyRate;
use App\Models\CurrencyType;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Exchange rates are set by each property owner (usually daily) as
 * LKR per 1 unit of a foreign currency.
 *
 * Every money transaction (booking, payment, refund) must store
 * snapshot() alongside its amount, so reports can total income in LKR
 * using the rate that applied when the transaction happened.
 */
class ExchangeRates
{
    // Latest rate the owner set on or before the given day; null if never set
    public function latestRate(int $ownerId, CurrencyType $currency, ?CarbonInterface $on = null): ?float
    {
        if ($currency->is_base) {
            return 1.0;
        }

        $rate = CurrencyRate::where('property_owner_id', $ownerId)
            ->where('currency_type_id', $currency->id)
            ->whereDate('rate_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('rate_date')
            ->orderByDesc('id')
            ->first();

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Values to store on a transaction row:
     * currency_type_id, exchange_rate (LKR per unit), amount_lkr.
     */
    public function snapshot(int $ownerId, CurrencyType $currency, float $amount): array
    {
        $rate = $this->latestRate($ownerId, $currency);

        if ($rate === null) {
            throw new RuntimeException("No {$currency->code} exchange rate has been set by property owner {$ownerId}.");
        }

        return [
            'currency_type_id' => $currency->id,
            'exchange_rate' => $rate,
            'amount_lkr' => round($amount * $rate, 2),
        ];
    }

    // Foreign currencies the owner has not set a rate for today
    public function missingTodayFor(int $ownerId): \Illuminate\Support\Collection
    {
        $setToday = CurrencyRate::where('property_owner_id', $ownerId)
            ->whereDate('rate_date', now()->toDateString())
            ->pluck('currency_type_id');

        return CurrencyType::active()
            ->where('is_base', false)
            ->whereNotIn('id', $setToday)
            ->get();
    }
}
