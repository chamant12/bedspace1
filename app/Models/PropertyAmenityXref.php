<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAmenityXref extends Model
{
    protected $fillable = ['property_id', 'amenity_id'];
}
