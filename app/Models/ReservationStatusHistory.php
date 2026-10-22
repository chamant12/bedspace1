<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Audit trail: who changed a reservation's status, when and why
class ReservationStatusHistory extends Model
{
    use Concerns\NeverDeleted;

    const UPDATED_AT = null;

    protected $table = 'reservation_status_history';

    protected $casts = [
        'from_status' => ReservationStatus::class,
        'to_status' => ReservationStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
