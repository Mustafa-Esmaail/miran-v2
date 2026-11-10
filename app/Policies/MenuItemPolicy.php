<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menus.viewAny');
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menus.view');
    }

    public function create(User $user): bool
    {
        return $user->can('menus.create');
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menus.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('menus.delete');
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menus.delete');
    }
}
