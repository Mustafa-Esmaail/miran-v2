<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;

class PaymentFailed extends Component
{
    public string $order_no;

    public function mount(string $order_no): void
    {
        $this->order_no = $order_no;

        Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $order_no)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.payment-failed')
            ->layout('layouts.app');
    }
}
