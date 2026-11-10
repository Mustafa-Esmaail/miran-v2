<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.viewAny');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('orders.delete');
    }

    public function delete(User $user, Order $order): bool
    {
        if (! $user->can('orders.delete')) {
            return false;
        }

        return $order->status !== OrderStatus::Paid;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('orders.delete')) {
            return false;
        }

        return $order->status !== OrderStatus::Paid;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('orders.delete');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }
}
