<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Rooms closed by the owner and booked by guests, per property x room type x date
class RoomInventory extends Model
{
    use Concerns\NeverDeleted;

    protected $table = 'room_inventory';

    protected $fillable = ['property_id', 'roomType_id', 'inventory_date', 'closed_rooms', 'booked_rooms'];

    protected $casts = [
        'closed_rooms' => 'integer',
        'booked_rooms' => 'integer',
    ];
}
