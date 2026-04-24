<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateType extends Model
{
    protected $table = 'ratetypes';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
