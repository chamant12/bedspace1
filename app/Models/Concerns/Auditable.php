<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Records creation and every change of the model in the audit log
 * (which fields changed, from what to what, by whom).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $values = collect($model->getAttributes())->except(Audit::HIDDEN)->all();
            Audit::log('created', $model, null, collect($values)->map(fn ($v) => ['from' => null, 'to' => $v])->all());
        });

        static::updated(function ($model) {
            $changes = Audit::diff($model);
            if ($changes) {
                Audit::log('updated', $model, null, $changes);
            }
        });
    }
}
