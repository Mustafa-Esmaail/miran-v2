<?php

namespace App\Repositories\Eloquent;

use App\Models\Blog;
use App\Repositories\Contracts\BlogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class BlogRepository implements BlogRepositoryInterface
{
    public function query(): Builder
    {
        return Blog::query();
    }

    public function create(array $data): Blog
    {
        return Blog::create($data);
    }

    public function findById(int $id): ?Blog
    {
        return Blog::query()->find($id);
    }

    public function findBySlug(string $slug): ?Blog
    {
        return Blog::query()->where('slug', $slug)->first();
    }
}
