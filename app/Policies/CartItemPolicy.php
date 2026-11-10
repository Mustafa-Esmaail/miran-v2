<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cart_items.viewAny');
    }

    public function view(User $user, CartItem $cartItem): bool
    {
        return $user->can('cart_items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cart_items.create');
    }

    public function update(User $user, CartItem $cartItem): bool
    {
        return $user->can('cart_items.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('cart_items.delete');
    }

    public function delete(User $user, CartItem $cartItem): bool
    {
        return $user->can('cart_items.delete');
    }
}
