<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An owner's exchange rate for one currency on one day, expressed as
 * base-currency (LKR) units per 1 unit of the currency.
 *
 * No longer used: currencies are not converted (LKR and USD amounts are
 * totalled separately). Kept for the history of rates entered earlier.
 */
class CurrencyRate extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $table = 'currency_rates';

    protected $casts = [
        'rate_date' => 'date',
        'rate' => 'decimal:2',
    ];

    public function currencyType(): BelongsTo
    {
        return $this->belongsTo(CurrencyType::class);
    }
}
