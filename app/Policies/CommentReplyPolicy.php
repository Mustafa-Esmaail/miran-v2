<?php

namespace App\Policies;

use App\Models\CommentReply;
use App\Models\User;

class CommentReplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('comment_replies.viewAny');
    }

    public function view(User $user, CommentReply $commentReply): bool
    {
        return $user->can('comment_replies.view');
    }

    public function create(User $user): bool
    {
        return $user->can('comment_replies.create');
    }

    public function update(User $user, CommentReply $commentReply): bool
    {
        return $user->can('comment_replies.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('comment_replies.delete');
    }

    public function delete(User $user, CommentReply $commentReply): bool
    {
        return $user->can('comment_replies.delete');
    }
}
