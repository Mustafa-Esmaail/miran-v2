<?php

namespace App\Repositories\Eloquent;

use App\Enums\BlogCategoryStatus;
use App\Models\BlogCategory;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use Illuminate\Support\Collection;

class BlogCategoryRepository implements BlogCategoryRepositoryInterface
{
    public function getActiveOrderedByName(string $locale): Collection
    {
        return BlogCategory::query()
            ->where('status', BlogCategoryStatus::Active)
            ->orderBy("title->{$locale}")
            ->get();
    }

    public function getAllOrderedByName(string $locale): Collection
    {
        return BlogCategory::query()
            ->orderBy("title->{$locale}")
            ->get();
    }
}
