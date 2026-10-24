<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
