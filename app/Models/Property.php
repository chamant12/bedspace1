<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    // property_owner_id is deliberately not fillable; it is set from the logged-in user
    protected $fillable = [
        'property_name',
        'city_id',
        'address',
        'location',
        'cancellation_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cancellation_days' => 'integer',
    ];

    // Non-refundable window before check-in: the owner's setting, or the platform default
    public function effectiveCancellationDays(): int
    {
        return (int) ($this->cancellation_days ?? config('bedspace.free_cancellation_days'));
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PropertyContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(PropertyContact::class)->where('is_primary', true);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function propertyOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'property_owner_id','id');
    }

    public function rateTypes(): HasMany
    {
        return $this->hasMany(RateType::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }
}
