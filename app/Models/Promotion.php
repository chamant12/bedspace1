<?php

namespace App\Models;

use App\Enums\PromotionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provisioned for the promotions phase. The booking price calculation will
 * call isApplicable() / discountOn() for each active promotion of a property.
 */
class Promotion extends Model
{
    use Concerns\NeverDeleted;

    const DISCOUNT_PERCENTAGE = 'percentage';
    const DISCOUNT_FIXED_AMOUNT = 'fixed_amount';

    protected $casts = [
        'promotion_type' => PromotionType::class,
        'discount_value' => 'decimal:2',
        'booking_from' => 'date',
        'booking_to' => 'date',
        'stay_from' => 'date',
        'stay_to' => 'date',
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'promotion_room_types', 'promotion_id', 'roomType_id')
            ->wherePivot('is_active', true);
    }

    public function isApplicable(CarbonInterface $bookedOn, CarbonInterface $checkIn, CarbonInterface $checkOut, int $roomTypeId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $daysBeforeArrival = $bookedOn->copy()->startOfDay()->diffInDays($checkIn->copy()->startOfDay());
        $nights = $checkIn->copy()->startOfDay()->diffInDays($checkOut->copy()->startOfDay());

        return ($this->min_days_before_arrival === null || $daysBeforeArrival >= $this->min_days_before_arrival)
            && ($this->max_days_before_arrival === null || $daysBeforeArrival <= $this->max_days_before_arrival)
            && ($this->min_nights === null || $nights >= $this->min_nights)
            && ($this->booking_from === null || $bookedOn->gte($this->booking_from->startOfDay()))
            && ($this->booking_to === null || $bookedOn->lte($this->booking_to->endOfDay()))
            && ($this->stay_from === null || $checkIn->gte($this->stay_from->startOfDay()))
            && ($this->stay_to === null || $checkIn->lte($this->stay_to->endOfDay()))
            && ($this->roomTypes->isEmpty() || $this->roomTypes->contains('id', $roomTypeId));
    }

    // Discount on a stay total; never more than the total itself. A fixed
    // amount only applies to totals in the promotion's own currency.
    public function discountOn(float $amount, int $currencyTypeId): float
    {
        if ($this->discount_type === self::DISCOUNT_PERCENTAGE) {
            $discount = $amount * ((float) $this->discount_value / 100);
        } elseif ((int) $this->currency_type_id === $currencyTypeId) {
            $discount = (float) $this->discount_value;
        } else {
            return 0.0;
        }

        return round(min($discount, $amount), 2);
    }
}
