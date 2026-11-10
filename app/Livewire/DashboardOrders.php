<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class DashboardOrders extends Component
{
    public function mount(): void
    {
        $orderNo = session()->pull('pending_payment_order_no');

        if ($orderNo && Order::query()->where('user_id', auth()->id())->where('order_no', $orderNo)->exists()) {
            $this->redirectRoute('dashboard.orders.show', ['order_no' => $orderNo], navigate: false);
        }
    }

    public function render(): View
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with(['items.seats', 'payments', 'bankTransferReceipt'])
            ->latest()
            ->get()
            ->map(function ($order) {
                $courseCount = $order->items
                    ->where('purchasable_type', \App\Models\CourseGroup::class)
                    ->sum('quantity');
                $bundleCount = $order->items
                    ->where('purchasable_type', \App\Models\Bundle::class)
                    ->sum('quantity');
                $assignedSeats = $order->items
                    ->flatMap(fn ($item) => $item->seats)
                    ->whereIn('status', [
                        \App\Enums\OrderItemSeatStatus::Invited,
                        \App\Enums\OrderItemSeatStatus::Enrolled,
                    ])
                    ->count();
                $availableSeats = $order->items
                    ->flatMap(fn ($item) => $item->seats)
                    ->where('status', \App\Enums\OrderItemSeatStatus::Unassigned)
                    ->count();
                $latestPayment = $order->payments->sortByDesc('created_at')->first();
                $paymentMethod = $latestPayment?->provider ?? ($order->bankTransferReceipt ? 'bank_transfer' : 'unpaid');
                $paymentStatus = $latestPayment?->status?->value ?? ($order->bankTransferReceipt ? 'pending' : 'unpaid');

                return [
                    'order_no' => $order->order_no,
                    'status' => $this->translateOrderStatus($order->status->value),
                    'total_amount' => $order->total_amount,
                    'currency' => $this->translateCurrency($order->currency),
                    'created_at' => $order->created_at->toDateString(),
                    'course_count' => $courseCount,
                    'bundle_count' => $bundleCount,
                    'assigned_seats' => $assignedSeats,
                    'available_seats' => $availableSeats,
                    'payment_method' => $this->translatePaymentMethod($paymentMethod),
                    'payment_status' => $this->translatePaymentStatus($paymentStatus),
                ];
            });

        return view('livewire.dashboard-orders', ['orders' => $orders])
            ->layout('layouts.app');
    }

    private function translateOrderStatus(string $status): string
    {
        $label = __('web.orders.status_values.'.$status);

        return $label !== 'web.orders.status_values.'.$status ? $label : Str::headline($status);
    }

    private function translatePaymentStatus(string $status): string
    {
        $label = __('web.orders.payment_status_values.'.$status);

        return $label !== 'web.orders.payment_status_values.'.$status ? $label : Str::headline($status);
    }

    private function translatePaymentMethod(string $method): string
    {
        $label = __('web.orders.payment_method_values.'.$method);

        return $label !== 'web.orders.payment_method_values.'.$method ? $label : Str::headline($method);
    }

    private function translateCurrency(string $currency): string
    {
        $key = strtolower($currency);
        $label = __('web.common.currency.'.$key);

        return $label !== 'web.common.currency.'.$key ? $label : $currency;
    }
}
