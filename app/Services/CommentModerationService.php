<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentReply;
use Illuminate\Support\Facades\DB;

class CommentModerationService
{
    public function approve(Comment $comment): Comment
    {
        $comment->forceFill(['approved' => true])->save();

        return $comment;
    }

    public function hide(Comment $comment): Comment
    {
        $comment->forceFill(['hidden' => true])->save();

        return $comment;
    }

    public function show(Comment $comment): Comment
    {
        $comment->forceFill(['hidden' => false])->save();

        return $comment;
    }

    public function approveReply(CommentReply $reply): CommentReply
    {
        $reply->forceFill(['approved' => true])->save();

        return $reply;
    }

    public function hideReply(CommentReply $reply): CommentReply
    {
        $reply->forceFill(['hidden' => true])->save();

        return $reply;
    }

    public function showReply(CommentReply $reply): CommentReply
    {
        $reply->forceFill(['hidden' => false])->save();

        return $reply;
    }

    public function approveMany(array $ids): void
    {
        DB::table('comments')->whereIn('id', $ids)->update([
            'approved' => true,
            'updated_at' => now(),
        ]);
    }

    public function hideMany(array $ids): void
    {
        DB::table('comments')->whereIn('id', $ids)->update([
            'hidden' => true,
            'updated_at' => now(),
        ]);
    }

    public function showMany(array $ids): void
    {
        DB::table('comments')->whereIn('id', $ids)->update([
            'hidden' => false,
            'updated_at' => now(),
        ]);
    }
}
