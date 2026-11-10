<?php

namespace App\Livewire;

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Gtm\GtmItemFormatter;
use Illuminate\View\View;
use Livewire\Component;

class PaymentSuccess extends Component
{
    public string $order_no;

    public ?array $gtmPurchase = null;

    public ?array $gtmSignUp = null;

    public ?array $invoice = null;

    /** @var array<int, array{title: string, quantity: int, price_amount: int, total_amount: int}> */
    public array $invoiceItems = [];

    public function mount(string $order_no): void
    {
        $this->order_no = $order_no;

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $order_no)
            ->with('items', 'payments', 'user')
            ->firstOrFail();

        $this->invoice = [
            'customer_name' => $order->customer_name,
            'phone' => $order->customer_phone,
            'email' => $order->user?->email,
            'currency' => $order->currency,
            'total_amount' => $order->total_amount,
        ];

        $this->invoiceItems = $order->items->map(fn (OrderItem $item): array => [
            'title' => $item->title_snapshot,
            'quantity' => $item->quantity,
            'price_amount' => $item->price_amount,
            'total_amount' => $item->price_amount * $item->quantity,
        ])->all();

        if ((int) session()->pull('gtm_purchase_order_id') === $order->id) {
            $this->gtmPurchase = [
                'transaction_id' => $order->order_no,
                'currency' => $order->currency,
                'value' => $order->total_amount,
                'coupon' => $order->coupon?->code,
                'items' => GtmItemFormatter::fromOrderItems($order->items),
            ];
        }

        $signUpEnrollmentIds = session()->pull('gtm_signup_enrollment_ids', []);
        if (! empty($signUpEnrollmentIds)) {
            $enrollments = Enrollment::query()
                ->where('user_id', auth()->id())
                ->whereIn('id', $signUpEnrollmentIds)
                ->with('course')
                ->get();

            if ($enrollments->isNotEmpty()) {
                $this->gtmSignUp = [
                    'method' => $order->payments->sortByDesc('created_at')->first()?->provider ?? 'online',
                    'items' => GtmItemFormatter::fromEnrollments($enrollments),
                ];
            }
        }
    }

    public function render(): View
    {
        return view('livewire.payment-success')
            ->layout('layouts.app');
    }
}
