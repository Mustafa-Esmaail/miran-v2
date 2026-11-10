<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menus.viewAny');
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->can('menus.view');
    }

    public function create(User $user): bool
    {
        return $user->can('menus.create');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->can('menus.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('menus.delete');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->can('menus.delete');
    }
}
