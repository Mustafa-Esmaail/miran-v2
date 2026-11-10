<?php

namespace App\Repositories\Eloquent;

use App\Models\CommentReply;
use App\Repositories\Contracts\CommentReplyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CommentReplyRepository implements CommentReplyRepositoryInterface
{
    public function query(): Builder
    {
        return CommentReply::query();
    }

    public function create(array $data): CommentReply
    {
        return CommentReply::query()->create($data);
    }

    public function findById(int $id): ?CommentReply
    {
        return CommentReply::query()->find($id);
    }
}
