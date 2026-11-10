<?php

namespace App\Repositories\Contracts;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;

interface BlogRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): Blog;

    public function findById(int $id): ?Blog;

    public function findBySlug(string $slug): ?Blog;
}
