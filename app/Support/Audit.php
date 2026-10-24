<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes audit log entries. Model changes are recorded automatically by
 * the Auditable trait; bulk actions (rates calendar saves, inventory
 * ranges...) call Audit::log() with a summary instead of one row per cell.
 * Failures to audit never break the action itself.
 */
class Audit
{
    // Never stored in the log
    const HIDDEN = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function log(string $action, ?Model $subject = null, ?string $description = null, ?array $changes = null, ?int $propertyId = null): void
    {
        try {
            $log = new AuditLog();
            $log->user_id = auth()->id();
            $log->action = $action;
            $log->subject_type = $subject ? class_basename($subject) : null;
            $log->subject_id = $subject?->getKey();
            $log->property_id = $propertyId ?? ($subject ? self::propertyIdOf($subject) : null);
            $log->description = $description ? mb_substr($description, 0, 500) : null;
            $log->changes = $changes ?: null;
            $log->ip_address = app()->runningInConsole() ? null : request()->ip();
            $log->created_at = now();
            $log->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // {"field": {"from": old, "to": new}} for the attributes that changed
    public static function diff(Model $model): array
    {
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, self::HIDDEN, true)) {
                continue;
            }
            $changes[$field] = ['from' => $model->getOriginal($field), 'to' => $new];
        }
        return $changes;
    }

    // Property the record belongs to, so owners can see their own audit trail
    public static function propertyIdOf(Model $model): ?int
    {
        return match (true) {
            $model instanceof \App\Models\Property => $model->id,
            isset($model->property_id) => (int) $model->property_id,
            isset($model->reservation_id) => (int) \App\Models\Reservation::whereKey($model->reservation_id)->value('property_id'),
            default => null,
        };
    }
}
