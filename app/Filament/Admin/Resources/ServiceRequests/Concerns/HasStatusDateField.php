<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Models\ServiceRequestStatus;

trait HasStatusDateField
{
    private static function statusRequiresDate(mixed $status): bool
    {
        if (blank($status)) {
            return false;
        }

        static $cache = [];

        return $cache[$status] ??= (bool) ServiceRequestStatus::query()->where('slug', $status)->value('requires_date');
    }
}
