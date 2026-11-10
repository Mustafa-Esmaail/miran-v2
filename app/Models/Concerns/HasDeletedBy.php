<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasDeletedBy
{
    public static function bootHasDeletedBy(): void
    {
        static::deleting(function (self $model): void {
            $model->deleted_by = auth('admin')->id() ?? auth()->id();
            $model->saveQuietly();
        });

        static::restoring(function (self $model): void {
            $model->deleted_by = null;
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
