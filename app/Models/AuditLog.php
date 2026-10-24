<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Append-only audit trail; written through App\Support\Audit
class AuditLog extends Model
{
    use Concerns\NeverDeleted;

    const UPDATED_AT = null;

    protected $casts = [
        'changes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
