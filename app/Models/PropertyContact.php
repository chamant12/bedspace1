<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyContact extends Model
{
    protected $fillable = ['name', 'mobile', 'email', 'is_primary'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Optional login account for the contact person
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
