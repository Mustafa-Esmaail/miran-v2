<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;

class DashboardOrderRedirecting extends Component
{
    public string $order_no;

    public ?string $redirectUrl = null;

    public function mount(string $order_no): void
    {
        $this->order_no = $order_no;

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $order_no)
            ->firstOrFail();

        $latestPayment = $order->payments()->latest()->first();

        // Already paid (e.g. the webhook landed while the user was away, or they
        // navigated back here after completing payment) — don't offer to pay again.
        if ($order->status === OrderStatus::Paid || $latestPayment?->status === PaymentStatus::Paid) {
            $this->redirectRoute('payment-success', ['order_no' => $order_no], navigate: false);

            return;
        }

        $redirectUrl = (string) ($latestPayment?->payload['redirect_url'] ?? '');

        if ($redirectUrl === '') {
            $this->redirectRoute('dashboard.orders.show', ['order_no' => $order_no], navigate: false);

            return;
        }

        $this->redirectUrl = $redirectUrl;
    }

    public function render(): View
    {
        return view('livewire.dashboard-order-redirecting')
            ->layout('layouts.app');
    }
}
