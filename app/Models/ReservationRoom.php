<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// One line of a reservation: N rooms of a room type x occupancy x meal plan
class ReservationRoom extends Model
{
    use Concerns\NeverDeleted;

    protected $casts = [
        'nightly_rates' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'roomType_id');
    }

    public function occupancy(): BelongsTo
    {
        return $this->belongsTo(Occupancy::class);
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class, 'mealType_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(ReservationDiscount::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class)->whereNull('released_at');
    }
}
