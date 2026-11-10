<?php

namespace App\Repositories\Contracts;

use App\Models\CommentReply;
use Illuminate\Database\Eloquent\Builder;

interface CommentReplyRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): CommentReply;

    public function findById(int $id): ?CommentReply;
}
