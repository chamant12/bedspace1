<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status changes go through App\Services\Reservations::transition() so
 * inventory and the status history stay consistent.
 */
class Reservation extends Model
{
    use Concerns\NeverDeleted;
    use Concerns\Auditable;

    protected $casts = [
        'status' => ReservationStatus::class,
        'payment_method' => PaymentMethod::class,
        'check_in' => 'date',
        'check_out' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'total_lkr' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'cancellation_days' => 'integer',
        'free_cancellation_until' => 'date',
        'refund_due' => 'decimal:2',
        'amount_refunded' => 'decimal:2',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function guestProfile(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyType::class, 'currency_type_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReservationStatusHistory::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    // Refund still to be paid back to the guest
    public function refundRemaining(): float
    {
        return max(0, round((float) $this->refund_due - (float) $this->amount_refunded, 2));
    }

    public function outstanding(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function money(float|string $amount): string
    {
        return $this->currency->code.' '.number_format((float) $amount, 2);
    }
}
