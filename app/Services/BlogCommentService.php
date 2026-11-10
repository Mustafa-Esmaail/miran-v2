<?php

namespace App\Services;

use App\Enums\BlogCommentStatus;
use App\Models\Blog;
use App\Models\BlogComment;
use App\Models\User;
use App\Repositories\Contracts\BlogCommentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BlogCommentService
{
    public function __construct(private readonly BlogCommentRepositoryInterface $comments) {}

    public function addComment(Blog $blog, User $user, string $content): BlogComment
    {
        $locale = app()->getLocale();

        return $this->comments->create([
            'blog_id' => $blog->id,
            'user_id' => $user->id,
            'content' => [$locale => $content],
            'status' => BlogCommentStatus::Pending,
        ]);
    }

    public function approve(BlogComment $comment): BlogComment
    {
        $comment->forceFill(['status' => BlogCommentStatus::Approved])->save();

        return $comment;
    }

    public function reject(BlogComment $comment): BlogComment
    {
        $comment->forceFill(['status' => BlogCommentStatus::Rejected])->save();

        return $comment;
    }

    public function approveMany(array $ids): void
    {
        DB::table('blog_comments')->whereIn('id', $ids)->update([
            'status' => BlogCommentStatus::Approved->value,
            'updated_at' => now(),
        ]);
    }

    public function rejectMany(array $ids): void
    {
        DB::table('blog_comments')->whereIn('id', $ids)->update([
            'status' => BlogCommentStatus::Rejected->value,
            'updated_at' => now(),
        ]);
    }
}
