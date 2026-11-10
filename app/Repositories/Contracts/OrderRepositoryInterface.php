<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function createOrder(array $data, array $items): Order;

    public function findByOrderNo(string $orderNo): ?Order;

    public function markPaid(Order $order): Order;

    public function markRejected(Order $order, ?string $notes): Order;

    public function countToday(): int;

    public function countThisMonth(): int;
}
