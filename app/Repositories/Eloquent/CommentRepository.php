<?php

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CommentRepository implements CommentRepositoryInterface
{
    public function query(): Builder
    {
        return Comment::query();
    }

    public function create(array $data): Comment
    {
        return Comment::query()->create($data);
    }

    public function findById(int $id): ?Comment
    {
        return Comment::query()->find($id);
    }
}
