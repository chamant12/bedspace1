<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function propertries(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
