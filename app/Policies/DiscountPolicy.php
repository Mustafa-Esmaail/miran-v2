<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('discounts.viewAny');
    }

    public function view(User $user, Discount $discount): bool
    {
        return $user->can('discounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('discounts.create');
    }

    public function update(User $user, Discount $discount): bool
    {
        return $user->can('discounts.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('discounts.delete');
    }

    public function delete(User $user, Discount $discount): bool
    {
        return $user->can('discounts.delete');
    }
}
