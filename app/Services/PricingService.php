<?php

namespace App\Services;

use App\DTOs\CartTotalsDto;
use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponTarget;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Repositories\Contracts\CouponRepositoryInterface;

class PricingService
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function calculateTotals(Cart $cart): CartTotalsDto
    {
        $subtotal = $cart->items->sum(fn ($item) => $item->price_amount * $item->quantity);
        $discount = 0;
        $taxRate = (float) config('lms.tax_rate', 0);
        $tax = 0;

        if ($cart->coupon_id) {
            $coupon = $cart->coupon;
            if ($coupon && $this->isCouponValid($coupon, $cart->user_id, $subtotal, $cart)) {
                $discount = $this->calculateDiscount($coupon, $subtotal);
            }
        }

        if ($taxRate > 0) {
            $tax = (int) round(max(0, $subtotal - $discount) * ($taxRate / 100));
        }

        $total = max(0, $subtotal - $discount + $tax);

        return new CartTotalsDto($subtotal, $discount, $tax, $total, config('lms.currency'));
    }

    /**
     * @return array{subtotal:int, discount:int, tax:int, total:int}
     */
    public function calculateOrderTotals(Order $order): array
    {
        $subtotal = $order->items->sum(fn ($item) => $item->price_amount * $item->quantity);
        $discount = 0;
        $taxRate = (float) config('lms.tax_rate', 0);
        $tax = 0;

        if ($order->coupon_id) {
            $coupon = $order->coupon;
            if ($coupon && $this->isOrderCouponValid($coupon, $order->user_id, $subtotal, $order)) {
                $discount = $this->calculateDiscount($coupon, $subtotal);
            }
        }

        if ($taxRate > 0) {
            $tax = (int) round(max(0, $subtotal - $discount) * ($taxRate / 100));
        }

        $total = max(0, $subtotal - $discount + $tax);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    public function isCouponValid(Coupon $coupon, ?int $userId, int $subtotal, ?Cart $cart = null): bool
    {
        return $this->couponRejectionReason($coupon, $userId, $subtotal, $cart) === null;
    }

    public function isOrderCouponValid(Coupon $coupon, ?int $userId, int $subtotal, ?Order $order = null): bool
    {
        return $this->orderCouponRejectionReason($coupon, $userId, $subtotal, $order) === null;
    }

    /**
     * Returns null when the coupon is valid, or a reason code describing why it was rejected.
     */
    public function couponRejectionReason(Coupon $coupon, ?int $userId, int $subtotal, ?Cart $cart = null): ?string
    {
        return $this->rejectionReasonForItems($coupon, $userId, $subtotal, $cart?->items);
    }

    /**
     * Returns null when the coupon is valid, or a reason code describing why it was rejected.
     */
    public function orderCouponRejectionReason(Coupon $coupon, ?int $userId, int $subtotal, ?Order $order = null): ?string
    {
        return $this->rejectionReasonForItems($coupon, $userId, $subtotal, $order?->items);
    }

    /**
     * Shared by the cart and order coupon checks so rules like min_items_count and the
     * applies_to/target match are enforced identically on both, instead of the order path
     * silently skipping checks the cart path performs.
     */
    private function rejectionReasonForItems(Coupon $coupon, ?int $userId, int $subtotal, ?iterable $items): ?string
    {
        if (! $coupon->is_active) {
            return 'inactive';
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return 'not_started';
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return 'expired';
        }

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return 'min_order_amount';
        }

        if ($coupon->min_items_count && $items !== null && collect($items)->sum('quantity') < $coupon->min_items_count) {
            return 'min_items_count';
        }

        if ($coupon->usage_limit && $this->coupons->countRedemptions($coupon->id) >= $coupon->usage_limit) {
            return 'usage_limit';
        }

        if ($userId !== null && $coupon->per_user_limit && $this->coupons->countUserRedemptions($coupon->id, $userId) >= $coupon->per_user_limit) {
            return 'per_user_limit';
        }

        if ($items !== null && ! $this->itemsMatchCoupon($coupon, $items)) {
            return 'not_applicable';
        }

        return null;
    }

    public function calculateDiscount(Coupon $coupon, int $subtotal): int
    {
        if ($coupon->type === CouponType::Percent) {
            return (int) round($subtotal * ($coupon->value / 100));
        }

        return min($subtotal, $coupon->value);
    }

    public function calculateCourseFinalPrice(Course $course, ?float $basePrice = null): float
    {
        $price = $basePrice ?? (float) ($course->price ?? 0);

        return $course->calculateDiscountedPrice($price);
    }

    public function calculateBundleFinalPrice(Bundle $bundle, ?float $basePrice = null): float
    {
        $price = $basePrice ?? (float) ($bundle->price ?? 0);

        return $bundle->calculateDiscountedPrice($price);
    }

    /**
     * Every item in the cart/order must satisfy the coupon's applies_to restriction
     * (course/bundle/both) AND, if specific targets are configured, match at least
     * one of them. A single disqualifying item rejects the whole coupon.
     */
    private function itemsMatchCoupon(Coupon $coupon, iterable $items): bool
    {
        return collect($items)->every(fn ($item) => $this->itemMatchesCoupon($coupon, $item));
    }

    private function itemMatchesCoupon(Coupon $coupon, $item): bool
    {
        if (! $this->itemMatchesAppliesTo($coupon->applies_to, $item)) {
            return false;
        }

        if ($coupon->targets->count() > 0 && ! $this->itemMatchesAnyTarget($item, $coupon->targets)) {
            return false;
        }

        return true;
    }

    private function itemMatchesAppliesTo(?CouponAppliesTo $appliesTo, $item): bool
    {
        return match ($appliesTo) {
            CouponAppliesTo::Course => $item->purchasable instanceof CourseGroup,
            CouponAppliesTo::Bundle => $item->purchasable instanceof Bundle,
            default => true,
        };
    }

    private function itemMatchesAnyTarget($item, $targets): bool
    {
        foreach ($targets as $target) {
            if ($this->itemMatchesTarget($item, $target)) {
                return true;
            }
        }

        return false;
    }

    private function itemMatchesTarget($item, CouponTarget $target): bool
    {
        if ($target->target_type->value === 'course' &&
            $item->purchasable instanceof CourseGroup &&
            $item->purchasable->course_id === $target->target_id) {
            return true;
        }

        if ($target->target_type->value === 'course_group' &&
            $item->purchasable instanceof CourseGroup &&
            $item->purchasable_id === $target->target_id) {
            return true;
        }

        if ($target->target_type->value === 'category' &&
            $item->purchasable instanceof CourseGroup &&
            $item->purchasable->course?->category_id === $target->target_id) {
            return true;
        }

        if ($target->target_type->value === 'category' &&
            $item->purchasable instanceof Bundle &&
            $item->purchasable->category_id === $target->target_id) {
            return true;
        }

        if ($target->target_type->value === 'bundle' &&
            $item->purchasable instanceof Bundle &&
            $item->purchasable_id === $target->target_id) {
            return true;
        }

        return false;
    }
}
