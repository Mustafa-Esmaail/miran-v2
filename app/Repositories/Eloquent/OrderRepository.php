<?php

namespace App\Repositories\Eloquent;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function createOrder(array $data, array $items): Order
    {
        return DB::transaction(function () use ($data, $items) {
            $order = Order::query()->create($data);
            $order->items()->createMany($items);

            return $order->load('items');
        });
    }

    public function findByOrderNo(string $orderNo): ?Order
    {
        return Order::query()->where('order_no', $orderNo)->with(['items', 'payments', 'coupon'])->first();
    }

    public function markPaid(Order $order): Order
    {
        $order->update(['status' => OrderStatus::Paid]);

        return $order;
    }

    public function markRejected(Order $order, ?string $notes): Order
    {
        $order->update(['status' => OrderStatus::Rejected, 'notes' => $notes]);

        return $order;
    }

    public function countToday(): int
    {
        return $this->countBetween(now()->startOfDay(), now()->endOfDay());
    }

    public function countThisMonth(): int
    {
        return $this->countBetween(now()->startOfMonth(), now()->endOfMonth());
    }

    private function countBetween(Carbon $start, Carbon $end): int
    {
        return Order::query()->whereBetween('created_at', [$start, $end])->count();
    }
}
