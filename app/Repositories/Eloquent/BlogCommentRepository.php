<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogComment;
use App\Repositories\Contracts\BlogCommentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class BlogCommentRepository implements BlogCommentRepositoryInterface
{
    public function query(): Builder
    {
        return BlogComment::query();
    }

    public function create(array $data): BlogComment
    {
        return BlogComment::create($data);
    }

    public function findById(int $id): ?BlogComment
    {
        return BlogComment::query()->find($id);
    }

    public function forBlog(int $blogId): Builder
    {
        return BlogComment::query()->where('blog_id', $blogId);
    }
}
