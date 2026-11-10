<?php

namespace App\Observers;

use App\Enums\OrderSource;
use App\Models\Order;

class OrderObserver
{
    public function saving(Order $order): void
    {
        if (! $order->isDirty('referred_by_employee_id')) {
            return;
        }

        if ($order->referred_by_employee_id) {
            $order->source = OrderSource::Sales;

            return;
        }

        if ($order->source === OrderSource::Sales) {
            $order->source = $order->marketing_source ? OrderSource::Store : OrderSource::Site;
        }
    }
}
