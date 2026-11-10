<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('blog_comments.viewAny');
    }

    public function view(User $user, BlogComment $blogComment): bool
    {
        return $user->can('blog_comments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('blog_comments.create');
    }

    public function update(User $user, BlogComment $blogComment): bool
    {
        return $user->can('blog_comments.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('blog_comments.delete');
    }

    public function delete(User $user, BlogComment $blogComment): bool
    {
        return $user->can('blog_comments.delete');
    }
}
