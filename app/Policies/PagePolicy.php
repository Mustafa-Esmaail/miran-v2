<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pages.viewAny');
    }

    public function view(User $user, Page $page): bool
    {
        return $user->can('pages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pages.create');
    }

    public function update(User $user, Page $page): bool
    {
        return $user->can('pages.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('pages.delete');
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->can('pages.delete');
    }
}
