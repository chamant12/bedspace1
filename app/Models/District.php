<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    
}
