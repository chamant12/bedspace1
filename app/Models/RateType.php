<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateType extends Model
{
    use Concerns\NeverDeleted;

    protected $table = 'ratetypes';

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
