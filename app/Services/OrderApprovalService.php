<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class OrderApprovalService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CartRepositoryInterface $carts,
        private readonly OrderSeatService $seats,
        private readonly NotificationService $notifications,
        private readonly OrderAttributionService $attributions,
        private readonly GoogleSheetsService $sheets,
        private readonly SettingsService $settings,
    ) {}

    public function approve(Order $order): void
    {
        $admin = auth('admin')->user();
        abort_unless(! $admin || $admin->can('orders.update'), 403);
        $status = $order->status instanceof OrderStatus ? $order->status : OrderStatus::tryFrom((string) $order->status);
        if ($status !== OrderStatus::Paid) {
            $this->orders->markPaid($order);
        }

        $this->seats->activateSeatsForOrder($order);
        $this->attributions->createForOrderIfEligible($order);
        $this->attributions->createForCouponIfEligible($order);
        $this->recordCouponRedemption($order);
        $this->clearCart($order);
        $this->syncToSheet($order);
        $this->notifications->notifyPaymentSuccess($order);
    }

    private function syncToSheet(Order $order): void
    {
        if ($order->sheet_synced_at) {
            return;
        }

        if (! $this->settings->ordersSyncToSheetsEnabled()) {
            return;
        }

        $sheetId = $this->settings->ordersSheetId();
        if ($sheetId === '') {
            return;
        }

        try {
            $rows = $this->sheets->buildRowsForOrder($order);
            $allSucceeded = true;
            foreach ($rows as $row) {
                $allSucceeded = $this->sheets->appendRow($sheetId, $row) && $allSucceeded;
            }

            if ($allSucceeded) {
                $order->update(['sheet_synced_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::channel('google_sheets')->error('OrderApprovalService: failed to sync order to sheet.', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function recordCouponRedemption(Order $order): void
    {
        if (! $order->coupon_id || ! $order->user_id) {
            return;
        }

        $exists = CouponRedemption::query()
            ->where('coupon_id', $order->coupon_id)
            ->where('order_id', $order->id)
            ->exists();

        if ($exists) {
            return;
        }

        CouponRedemption::query()->create([
            'coupon_id' => $order->coupon_id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }

    private function clearCart(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $cart = $this->carts->getCartWithItems($order->user_id);
        if (! $cart) {
            return;
        }

        $this->carts->clearCart($cart->id);
    }
}
