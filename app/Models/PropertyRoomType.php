<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Number of rooms of an (owner-level) room type at one property
class PropertyRoomType extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $fillable = ['property_id', 'roomType_id', 'total_rooms', 'is_active'];

    protected $casts = [
        'total_rooms' => 'integer',
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
