<?php

namespace App\Models;

use App\Enums\PaymentChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment against a reservation. Status: pending (online, waiting for
 * the gateway) -> completed | failed. Created and completed only through
 * App\Services\Payments.
 */
class Payment extends Model
{
    use Concerns\NeverDeleted;

    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const FAILED = 'failed';

    // kind: refunds carry negative amount, amount_lkr and commission
    const KIND_PAYMENT = 'payment';
    const KIND_REFUND = 'refund';

    public function isRefund(): bool
    {
        return $this->kind === self::KIND_REFUND;
    }

    protected $casts = [
        'method' => PaymentChannel::class,
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_lkr' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_lkr' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyType::class, 'currency_type_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
