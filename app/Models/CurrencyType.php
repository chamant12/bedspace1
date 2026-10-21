<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CurrencyType extends Model
{
    use Concerns\NeverDeleted;

    const LKR = 'LKR';
    const USD = 'USD';

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // The currency reports are totalled in (LKR)
    public static function base(): self
    {
        return static::where('is_base', true)->firstOrFail();
    }

    public static function byCode(string $code): self
    {
        return static::where('code', $code)->firstOrFail();
    }
}
