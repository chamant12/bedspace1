<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// A hotel paying the platform the commission on payments it collected
class CommissionSettlement extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_lkr' => 'decimal:2', // before currencies were kept apart (no longer written)
        'settled_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyType::class, 'currency_type_id');
    }

    // Amount with its currency code, e.g. 'USD 120.00'
    public function money(): string
    {
        return ($this->currency?->code ?? 'LKR').' '.number_format((float) $this->amount, 2);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
