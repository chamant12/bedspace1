<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    // property_owner_id is deliberately not fillable; it is set from the logged-in user
    protected $fillable = [
        'property_name',
        'city_id',
        'contact_name',
        'contact_number',
        'contact_email',
        'address',
        'location',
    ];

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

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }
}
