<?php

namespace App\Policies;

use App\Models\BlogCategory;
use App\Models\User;

class BlogCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('blog_categories.viewAny');
    }

    public function view(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('blog_categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('blog_categories.create');
    }

    public function update(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('blog_categories.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('blog_categories.delete');
    }

    public function delete(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('blog_categories.delete');
    }
}
