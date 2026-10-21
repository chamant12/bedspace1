<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Business records are deactivated (is_active = false), never deleted.
 * Any attempt to delete a model instance fails loudly instead of losing data.
 *
 * Note: model events do not fire for query-builder deletes
 * (Model::where(...)->delete()); those must not be used either.
 */
trait NeverDeleted
{
    public static function bootNeverDeleted(): void
    {
        static::deleting(function ($model) {
            throw new LogicException(class_basename($model).' records cannot be deleted; deactivate them instead.');
        });
    }
}
