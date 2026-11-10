<?php

namespace App\Repositories\Contracts;

use App\Models\BlogComment;
use Illuminate\Database\Eloquent\Builder;

interface BlogCommentRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): BlogComment;

    public function findById(int $id): ?BlogComment;

    public function forBlog(int $blogId): Builder;
}
