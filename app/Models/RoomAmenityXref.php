<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAmenityXref extends Model
{
    use Concerns\NeverDeleted;

    protected $table = "roomType_amenity_xrefs";

    protected $fillable = ['roomType_id', 'amenity_id', 'is_active'];
}
