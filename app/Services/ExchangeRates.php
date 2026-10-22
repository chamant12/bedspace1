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
    /**
     * LKR per 1 unit of the currency: the latest rate the owner set on or
     * before the given day; if the owner never set one, the latest rate any
     * owner set. Null only if no rate exists at all.
     */
    public function latestRate(int $ownerId, CurrencyType $currency, ?CarbonInterface $on = null): ?float
    {
        if ($currency->is_base) {
            return 1.0;
        }

        $query = fn () => CurrencyRate::where('currency_type_id', $currency->id)
            ->whereDate('rate_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('rate_date')
            ->orderByDesc('id');

        $rate = $query()->where('property_owner_id', $ownerId)->first() ?? $query()->first();

        return $rate ? (float) $rate->rate : null;
    }

    // Converts an amount between currencies via LKR; null if a rate is missing
    public function convert(float $amount, CurrencyType $from, CurrencyType $to, int $ownerId): ?float
    {
        if ($from->id === $to->id) {
            return $amount;
        }

        $fromRate = $this->latestRate($ownerId, $from);
        $toRate = $this->latestRate($ownerId, $to);

        if ($fromRate === null || $toRate === null) {
            return null;
        }

        return round($amount * $fromRate / $toRate, 2);
    }

    /**
     * Values to store on a transaction row:
     * currency_type_id, exchange_rate (LKR per unit), amount_lkr.
     */
    public function snapshot(int $ownerId, CurrencyType $currency, float $amount): array
    {
        $rate = $this->latestRate($ownerId, $currency);

        if ($rate === null) {
            throw new RuntimeException("No {$currency->code} exchange rate has been set.");
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
