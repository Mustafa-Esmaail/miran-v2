<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface SettingsRepositoryInterface
{
    public function all(): Collection;

    public function upsert(array $rows): void;
}
