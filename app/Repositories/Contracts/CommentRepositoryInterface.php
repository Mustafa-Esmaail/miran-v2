<?php

namespace App\Repositories\Contracts;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

interface CommentRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): Comment;

    public function findById(int $id): ?Comment;
}
