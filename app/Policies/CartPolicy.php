<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('carts.viewAny');
    }

    public function view(User $user, Cart $cart): bool
    {
        return $user->can('carts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('carts.create');
    }

    public function update(User $user, Cart $cart): bool
    {
        return $user->can('carts.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('carts.delete');
    }

    public function delete(User $user, Cart $cart): bool
    {
        return $user->can('carts.delete');
    }
}
