<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Contracts\SettingsRepositoryInterface;

class SettingsRepository implements SettingsRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Setting::query()->orderBy('sort_order')->get();
    }

    public function upsert(array $rows): void
    {
        Setting::query()->upsert(
            $rows,
            ['key'],
            ['group', 'type', 'value', 'is_translatable', 'sort_order', 'updated_at']
        );
    }
}
