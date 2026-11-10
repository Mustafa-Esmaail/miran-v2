<?php

namespace App\Repositories\Contracts;

interface CategoryRepositoryInterface
{
    public function countAll(): int;

    public function getHomeCategories(int $limit = 6);

    public function getActiveOrderedByName(string $locale);
}
