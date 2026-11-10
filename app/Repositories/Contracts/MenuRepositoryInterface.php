<?php

namespace App\Repositories\Contracts;

use App\Models\Menu;
use Illuminate\Support\Collection;

interface MenuRepositoryInterface
{
    public function getActiveMenuByLocation(string $location): ?Menu;

    public function getActiveItemsForLocation(string $location): Collection;
}
