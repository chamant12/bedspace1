<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use Concerns\NeverDeleted;

    const UPDATED_AT = null;

    protected $guarded = [];
}
