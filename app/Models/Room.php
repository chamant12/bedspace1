<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A physical room number at a property. Not tied to inventory counts:
 * guests are assigned a room number at check-in (Phase 3).
 */
class Room extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'roomType_id');
    }
}
