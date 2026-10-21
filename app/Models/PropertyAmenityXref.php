<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAmenityXref extends Model
{
    use Concerns\NeverDeleted;

    protected $fillable = ['property_id', 'amenity_id', 'is_active'];
}
