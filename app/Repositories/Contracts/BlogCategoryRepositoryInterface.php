<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface BlogCategoryRepositoryInterface
{
    public function getActiveOrderedByName(string $locale): Collection;

    public function getAllOrderedByName(string $locale): Collection;
}
