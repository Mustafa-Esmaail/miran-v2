<?php

namespace App\Policies;

use App\Models\OrderAttribution;
use App\Models\User;

class OrderAttributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('order_attributions.viewAny');
    }

    public function view(User $user, OrderAttribution $orderAttribution): bool
    {
        return $user->can('order_attributions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('order_attributions.create');
    }

    public function update(User $user, OrderAttribution $orderAttribution): bool
    {
        return $user->can('order_attributions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('order_attributions.delete');
    }

    public function delete(User $user, OrderAttribution $orderAttribution): bool
    {
        return $user->can('order_attributions.delete');
    }
}
