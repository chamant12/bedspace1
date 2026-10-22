<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use Concerns\NeverDeleted;

    // property_owner_id is deliberately not fillable; it is set from the logged-in user
    protected $fillable = [
        'property_name',
        'city_id',
        'address',
        'location',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
    ];

    // Platform commission % for new bookings: the admin's rate for this property, or the platform default
    public function effectiveCommissionRate(): float
    {
        return (float) ($this->commission_rate ?? config('bedspace.default_commission_rate'));
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
