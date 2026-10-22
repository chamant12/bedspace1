<?php

namespace App\Services;

use App\Models\CurrencyType;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\RoomType;
use Carbon\CarbonInterface;

/**
 * Applies a property's promotions (early bird, last minute, long stay,
 * seasonal...) to the price of a stay.
 *
 * Rule: from the promotions whose conditions the stay meets, the single
 * best non-stackable one is applied, plus every stackable one (in priority
 * order). The total discount never exceeds the price.
 *
 * Promotions are created in a later phase; until then no rows exist and
 * this returns no discounts.
 */
class PromotionEngine
{
    /**
     * @return array<int, array{promotion: Promotion, description: string, amount: float}>
     */
    public function discountsFor(Property $property, RoomType $roomType, CarbonInterface $bookedOn,
                                 CarbonInterface $checkIn, CarbonInterface $checkOut, CurrencyType $currency, float $amount): array
    {
        $candidates = Promotion::with('roomTypes')
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Promotion $promotion) => $promotion->isApplicable($bookedOn, $checkIn, $checkOut, $roomType->id))
            ->map(fn (Promotion $promotion) => [
                'promotion' => $promotion,
                'description' => $promotion->name,
                'amount' => $promotion->discountOn($amount, $currency->id),
            ])
            ->filter(fn ($discount) => $discount['amount'] > 0);

        $bestExclusive = $candidates->reject(fn ($d) => $d['promotion']->is_stackable)->sortByDesc('amount')->first();
        $stackable = $candidates->filter(fn ($d) => $d['promotion']->is_stackable)->sortByDesc(fn ($d) => $d['promotion']->priority);

        $applied = [];
        $remaining = $amount;
        foreach (array_filter([$bestExclusive, ...$stackable->values()->all()]) as $discount) {
            $discount['amount'] = round(min($discount['amount'], $remaining), 2);
            if ($discount['amount'] <= 0) {
                break;
            }
            $remaining -= $discount['amount'];
            $applied[] = $discount;
        }

        return $applied;
    }
}
