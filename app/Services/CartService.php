<?php

namespace App\Services;

use App\DTOs\CartItemDto;
use App\DTOs\CartTotalsDto;
use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Support\Gtm\GtmEventQueue;
use App\Support\Gtm\GtmItemFormatter;
use App\Support\Purchasables\PurchasableResolver;
use App\Support\S3Url;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService
{
    private static array $itemsCache = [];

    private static array $totalsCache = [];

    private static array $couponCache = [];

    private static array $cartCache = [];

    private static array $couponRemovalReason = [];

    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly CouponRepositoryInterface $coupons,
        private readonly PricingService $pricing,
        private readonly PurchasableResolver $purchasables,
    ) {}

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function getCartItems(int|string $owner): array
    {
        $cacheKey = $this->cacheKey($owner);
        if (array_key_exists($cacheKey, self::$itemsCache)) {
            return self::$itemsCache[$cacheKey];
        }

        $cart = $this->cachedCart($owner);
        if (! $cart) {
            self::$itemsCache[$cacheKey] = [];

            return self::$itemsCache[$cacheKey];
        }

        $items = $cart->items->map(function (CartItem $item) {
            $purchasable = $item->purchasable;
            $imageUrl = $purchasable ? $this->purchasables->getImageUrl($purchasable) : null;
            $imageUrl = $imageUrl
                ? (str_starts_with($imageUrl, 'http') ? $imageUrl : S3Url::temporary($imageUrl))
                : null;

            $itemType = $purchasable ? $this->purchasables->getTypeLabel($purchasable) : 'unknown';
            $title = $purchasable ? $this->purchasables->getDisplayLabel($purchasable) : '';
            $subtitle = $purchasable ? $this->purchasables->getSubtitle($purchasable) : '';

            $originalPriceAmount = $item->price_amount;
            if ($purchasable instanceof \App\Models\Bundle) {
                $originalPriceAmount = (int) round($purchasable->price ?? $item->price_amount);
            }
            if ($purchasable instanceof \App\Models\CourseGroup) {
                $originalPriceAmount = $purchasable->course?->is_free
                    ? 0
                    : (int) round($purchasable->course?->price ?? $item->price_amount);
            }

            return new CartItemDto(
                $item->id,
                $item->purchasable_type,
                $item->purchasable_id,
                $itemType,
                $title,
                $subtitle,
                $imageUrl,
                $imageUrl,
                $item->price_amount,
                $originalPriceAmount,
                $item->quantity,
                $purchasable instanceof \App\Models\CourseGroup ? $purchasable->course_id : null,
            );
        })->all();

        self::$itemsCache[$cacheKey] = $items;

        return $items;
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function getTotals(int|string $owner): ?CartTotalsDto
    {
        $cacheKey = $this->cacheKey($owner);
        if (array_key_exists($cacheKey, self::$totalsCache)) {
            return self::$totalsCache[$cacheKey];
        }

        $cart = $this->cachedCart($owner);

        self::$totalsCache[$cacheKey] = $cart ? $this->pricing->calculateTotals($cart) : null;

        return self::$totalsCache[$cacheKey];
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     * @param  array<string, mixed>|null  $selectionSnapshot
     */
    public function addItem(
        int|string $owner,
        string $purchasableType,
        int $purchasableId,
        int $quantity = 1,
        ?array $selectionSnapshot = null
    ): CartItem {
        $cart = $this->resolveCart($owner);
        $purchasable = $this->purchasables->resolvePurchasable($purchasableType, $purchasableId);
        if (! $this->purchasables->isAvailable($purchasable)) {
            throw new \RuntimeException(__('web.course.group_unavailable'));
        }
        $finalPrice = $this->purchasables->getPriceAmount($purchasable);

        $item = DB::transaction(function () use ($cart, $owner, $purchasable, $finalPrice, $quantity, $selectionSnapshot) {
            $item = $cart->items()->updateOrCreate(
                [
                    'purchasable_type' => $purchasable::class,
                    'purchasable_id' => $purchasable->id,
                ],
                [
                    'price_amount' => $finalPrice,
                    'quantity' => max(1, $quantity),
                    'selection_snapshot' => $selectionSnapshot,
                ]
            );

            $this->logEvent('cart.add_item', [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'purchasable_type' => $purchasable::class,
                'purchasable_id' => $purchasable->id,
                'price_amount' => $finalPrice,
                'cart_item_id' => $item->id,
            ]);

            $this->clearCaches($owner);
            $this->revalidateCoupon($owner);

            return $item;
        });

        $lineQuantity = max(1, $quantity);
        GtmEventQueue::push('add_to_cart', [
            'currency' => 'SAR',
            'value' => $finalPrice * $lineQuantity,
            'items' => [GtmItemFormatter::fromPurchasable(
                $purchasable,
                $this->purchasables->getDisplayLabel($purchasable),
                $finalPrice,
                $lineQuantity,
            )],
        ]);

        return $item;
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function removeItem(int|string $owner, int $itemId): void
    {
        $cart = $this->cachedCart($owner);
        if (! $cart) {
            return;
        }

        $item = $cart->items()->whereKey($itemId)->first();
        $cart->items()->whereKey($itemId)->delete();

        $this->logEvent('cart.remove_item', [
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'cart_item_id' => $itemId,
            'purchasable_type' => $item?->purchasable_type,
            'purchasable_id' => $item?->purchasable_id,
        ]);

        $this->clearCaches($owner);
        $this->revalidateCoupon($owner);
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function updateQuantity(int|string $owner, int $itemId, int $quantity): ?CartItem
    {
        $cart = $this->cachedCart($owner);
        if (! $cart) {
            return null;
        }

        $item = $cart->items()->whereKey($itemId)->first();
        if (! $item) {
            return null;
        }

        $item->update(['quantity' => max(1, $quantity)]);

        $this->clearCaches($owner);
        $this->revalidateCoupon($owner);

        return $item;
    }

    public function validateCouponCode(string $code): bool
    {
        $coupon = $this->coupons->findActiveByCode($code);
        if (! $coupon) {
            return false;
        }

        if (! $coupon->is_active) {
            return false;
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return false;
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return false;
        }

        if ($coupon->usage_limit && $this->coupons->countRedemptions($coupon->id) >= $coupon->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Applies the coupon and returns null on success, or a rejection reason code
     * (e.g. 'invalid', 'not_applicable', 'expired', ...) when it could not be applied.
     *
     * @param  int|string  $owner  authenticated user id, or guest session id. The per-user
     *                             redemption limit is only enforced once $owner is a real user id.
     */
    public function applyCoupon(int|string $owner, string $code): ?string
    {
        $cart = $this->cachedCart($owner);
        if (! $cart) {
            return 'invalid';
        }

        $coupon = $this->coupons->findActiveByCode($code);
        if (! $coupon) {
            return 'invalid';
        }

        $userId = is_int($owner) ? $owner : null;
        $totals = $this->pricing->calculateTotals($cart);
        $reason = $this->pricing->couponRejectionReason($coupon, $userId, $totals->subtotalAmount, $cart);
        if ($reason !== null) {
            return $reason;
        }

        $cart->update(['coupon_id' => $coupon->id]);

        $this->logEvent('cart.apply_coupon', [
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
        ]);

        $this->clearCaches($owner);

        return null;
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function removeCoupon(int|string $owner): void
    {
        $cart = $this->cachedCart($owner);
        if (! $cart) {
            return;
        }

        $previousCouponId = $cart->coupon_id;
        $cart->update(['coupon_id' => null]);

        $this->logEvent('cart.remove_coupon', [
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'coupon_id' => $previousCouponId,
        ]);

        $this->clearCaches($owner);
    }

    /**
     * Returns and clears the reason the cart's coupon was auto-removed by the most
     * recent revalidateCoupon() call, so a UI layer can surface it once (e.g. a toast
     * after adding/removing/updating a cart item made the coupon no longer qualify).
     *
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function consumeCouponRemovalReason(int|string $owner): ?string
    {
        $cacheKey = $this->cacheKey($owner);
        $reason = self::$couponRemovalReason[$cacheKey] ?? null;
        unset(self::$couponRemovalReason[$cacheKey]);

        return $reason;
    }

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function getAppliedCouponCode(int|string $owner): ?string
    {
        $cacheKey = $this->cacheKey($owner);
        if (array_key_exists($cacheKey, self::$couponCache)) {
            return self::$couponCache[$cacheKey];
        }

        $cart = $this->cachedCart($owner);

        self::$couponCache[$cacheKey] = $cart?->coupon?->code;

        return self::$couponCache[$cacheKey];
    }

    /**
     * Empties the cart (items and applied coupon) without deleting the cart row itself.
     * Used once an order has been placed from it via a path that won't need the cart
     * again for retries (e.g. cash / bank transfer, or an approved order).
     *
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public function clearCart(int|string $owner): void
    {
        $cart = $this->cachedCart($owner);
        if (! $cart) {
            return;
        }

        $this->carts->clearCart($cart->id);
        $this->clearCaches($owner);
    }

    /**
     * Merges a guest cart (keyed by session id) into the user's cart, e.g. right after
     * login or registration. No-op if the guest never built up a cart for that session.
     */
    public function mergeGuestCartIntoUser(string $sessionId, int $userId): void
    {
        $this->carts->mergeSessionCartIntoUser($sessionId, $userId);

        $this->clearCaches($sessionId);
        $this->clearCaches($userId);
    }

    /**
     * Re-checks the cart's applied coupon after an item/quantity change and clears it
     * if it no longer qualifies (e.g. dropping below min_items_count, or an added item
     * that doesn't match the coupon's applies_to/target restriction), instead of
     * silently zeroing the discount while the UI keeps showing it as applied.
     *
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    private function revalidateCoupon(int|string $owner): void
    {
        $cart = $this->cachedCart($owner);
        if (! $cart || ! $cart->coupon_id || ! $cart->coupon) {
            return;
        }

        $userId = is_int($owner) ? $owner : null;
        $totals = $this->pricing->calculateTotals($cart);
        $reason = $this->pricing->couponRejectionReason($cart->coupon, $userId, $totals->subtotalAmount, $cart);
        if ($reason === null) {
            return;
        }

        $coupon = $cart->coupon;
        $cart->update(['coupon_id' => null]);

        $this->logEvent('cart.coupon_auto_removed', [
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'coupon_id' => $coupon->id,
            'reason' => $reason,
        ]);

        self::$couponRemovalReason[$this->cacheKey($owner)] = $reason;

        $this->clearCaches($owner);
    }

    private function resolveCart(int|string $owner): Cart
    {
        return is_int($owner)
            ? $this->carts->getOrCreateForUser($owner)
            : $this->carts->getOrCreateForSession($owner);
    }

    private function cachedCart(int|string $owner): ?Cart
    {
        $cacheKey = $this->cacheKey($owner);
        if (! array_key_exists($cacheKey, self::$cartCache)) {
            self::$cartCache[$cacheKey] = is_int($owner)
                ? $this->carts->getCartWithItems($owner)
                : $this->carts->getCartWithItemsBySession($owner);
        }

        return self::$cartCache[$cacheKey];
    }

    private function cacheKey(int|string $owner): string
    {
        return is_int($owner) ? 'u'.$owner : 's'.$owner;
    }

    private function logEvent(string $event, array $context = []): void
    {
        Log::channel('lms')->info($event, $context);
    }

    private function clearCaches(int|string $owner): void
    {
        $cacheKey = $this->cacheKey($owner);

        unset(
            self::$itemsCache[$cacheKey],
            self::$totalsCache[$cacheKey],
            self::$couponCache[$cacheKey],
            self::$cartCache[$cacheKey],
        );
    }
}
