<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function countAll(): int
    {
        return Category::query()->count();
    }

    public function getHomeCategories(int $limit = 5)
    {
        $cacheKey = 'home_categories:'.$limit;

        // return Cache::remember($cacheKey, 300, function () use ($limit) {
        return Category::query()
            ->where('is_active', true)
            ->withCount('courses')
            ->orderByDesc('courses_count')
            ->limit($limit)
            ->get();
        // });
    }

    public function getActiveOrderedByName(string $locale)
    {
        $cacheKey = 'categories.active_ordered:'.$locale;

        // return Cache::remember($cacheKey, 300, function () use ($locale) {
        return Category::query()
            ->where('is_active', true)
            ->orderBy("name->{$locale}")
            ->get();
        // });
    }
}
