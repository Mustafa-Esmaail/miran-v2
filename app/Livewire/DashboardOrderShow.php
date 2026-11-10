<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\Enrollment;
use App\Models\Order;
use App\Support\Gtm\GtmItemFormatter;
use App\Support\S3Url;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class DashboardOrderShow extends Component
{
    public string $order_no;

    public function mount(string $order_no): void
    {
        $this->order_no = $order_no;
    }

    public function render(): View
    {
        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $this->order_no)
            ->with('items', 'payments', 'bankTransferReceipt', 'invoice')
            ->firstOrFail();

        $latestPayment = $order->payments->sortByDesc('created_at')->first();
        $paymentMethod = $latestPayment?->provider ?? ($order->bankTransferReceipt ? 'bank_transfer' : 'unpaid');
        $paymentStatus = $latestPayment?->status?->value ?? ($order->bankTransferReceipt ? 'pending' : 'unpaid');
        $paymentMethodLabel = $this->translatePaymentMethod($paymentMethod);
        $paymentStatusLabel = $this->translatePaymentStatus($paymentStatus);
        $statusLabel = $this->translateOrderStatus($order->status->value);
        $currencyLabel = $this->translateCurrency($order->currency);

        $gtmPurchase = null;
        if ((int) session()->pull('gtm_purchase_order_id') === $order->id) {
            $gtmPurchase = [
                'transaction_id' => $order->order_no,
                'currency' => $order->currency,
                'value' => $order->total_amount,
                'coupon' => $order->coupon?->code,
                'items' => GtmItemFormatter::fromOrderItems($order->items),
            ];
        }

        $gtmSignUp = null;
        $signUpEnrollmentIds = session()->pull('gtm_signup_enrollment_ids', []);
        if (! empty($signUpEnrollmentIds)) {
            $enrollments = Enrollment::query()
                ->where('user_id', auth()->id())
                ->whereIn('id', $signUpEnrollmentIds)
                ->with('course')
                ->get();

            if ($enrollments->isNotEmpty()) {
                $gtmSignUp = [
                    'method' => $paymentMethod,
                    'items' => GtmItemFormatter::fromEnrollments($enrollments),
                ];
            }
        }

        $groupsByCourseId = Enrollment::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('course_group_id')
            ->pluck('course_group_id', 'course_id');

        $items = $order->items->map(function ($item) use ($groupsByCourseId) {
            $metadata = $item->metadata_snapshot ?? [];
            $imagePath = $metadata['image_path'] ?? null;
            $thumbnailUrl = null;
            if ($imagePath) {
                $thumbnailUrl = Str::startsWith($imagePath, ['http://', 'https://'])
                    ? $imagePath
                    : S3Url::temporary($imagePath);
            }

            $typeKey = $metadata['type_label'] ?? Str::snake(class_basename($item->purchasable_type));
            $typeLabel = __('web.orders.item_type.'.$typeKey);
            $courseId = $metadata['course_id'] ?? null;

            return [
                'title' => $item->title_snapshot,
                'subtitle' => $metadata['subtitle'] ?? ($metadata['group_title'] ?? ''),
                'price_amount' => $item->price_amount,
                'quantity' => $item->quantity,
                'total_amount' => $item->price_amount * $item->quantity,
                'type' => $typeLabel !== 'web.orders.item_type.'.$typeKey
                    ? $typeLabel
                    : ($metadata['type_label'] ?? Str::headline(class_basename($item->purchasable_type))),
                'thumbnail_url' => $thumbnailUrl,
                'course_slug' => $metadata['course_slug'] ?? null,
                'group_id' => $courseId ? ($groupsByCourseId[$courseId] ?? null) : null,
            ];
        });

        return view('livewire.dashboard-order-show', [
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $statusLabel,
                'total_amount' => $order->total_amount,
                'currency' => $currencyLabel,
                'created_at' => $order->created_at->toDateString(),
                'payment_method' => $paymentMethodLabel,
                'payment_status' => $paymentStatusLabel,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'notes' => $this->translateOrderNote($order->notes),
                'invoice_url' => ($order->invoice?->status === InvoiceStatus::Submitted && $order->invoice->invoice_path)
                    ? S3Url::temporary($order->invoice->invoice_path, 15)
                    : null,
            ],
            'items' => $items,
            'gtmPurchase' => $gtmPurchase,
            'gtmSignUp' => $gtmSignUp,
        ])->layout('layouts.app');
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

    private function translateOrderNote(?string $note): ?string
    {
        if (! $note) {
            return null;
        }

        $normalized = Str::of($note)->lower()->replace(' ', '_')->toString();
        $label = __('web.orders.notes.'.$normalized);

        return $label !== 'web.orders.notes.'.$normalized ? $label : $note;
    }
}
