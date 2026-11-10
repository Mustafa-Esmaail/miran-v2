<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('blogs.viewAny');
    }

    public function view(User $user, Blog $blog): bool
    {
        return $user->can('blogs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('blogs.create');
    }

    public function update(User $user, Blog $blog): bool
    {
        return $user->can('blogs.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('blogs.delete');
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $user->can('blogs.delete');
    }
}
