<?php

namespace App\Policies;

use App\Models\OrderItemSeat;
use App\Models\User;

class OrderItemSeatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('order_item_seats.viewAny');
    }

    public function view(User $user, OrderItemSeat $orderItemSeat): bool
    {
        return $user->can('order_item_seats.view');
    }

    public function create(User $user): bool
    {
        return $user->can('order_item_seats.create');
    }

    public function update(User $user, OrderItemSeat $orderItemSeat): bool
    {
        return $user->can('order_item_seats.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('order_item_seats.delete');
    }

    public function delete(User $user, OrderItemSeat $orderItemSeat): bool
    {
        return $user->can('order_item_seats.delete');
    }
}
