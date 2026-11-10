<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('order_items.viewAny');
    }

    public function view(User $user, OrderItem $orderItem): bool
    {
        return $user->can('order_items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('order_items.create');
    }

    public function update(User $user, OrderItem $orderItem): bool
    {
        if ($orderItem->order->status === OrderStatus::Paid) {
            return $user->can('order_items.update_paid');
        }

        return $user->can('order_items.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('order_items.delete');
    }

    public function delete(User $user, OrderItem $orderItem): bool
    {
        return $user->can('order_items.delete');
    }
}
