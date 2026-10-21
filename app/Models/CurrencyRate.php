<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use Concerns\NeverDeleted;

    protected $table = 'currency_rates';
}
