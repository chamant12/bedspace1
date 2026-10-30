<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Website-wide settings (brand, contact details, social links), edited in the CMS
class SiteSetting extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['key', 'value'];
}
