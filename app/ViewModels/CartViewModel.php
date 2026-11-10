<?php

namespace App\ViewModels;

use App\DTOs\CartItemDto;
use App\Services\CartService;

class CartViewModel
{
    public function __construct(
        public readonly array $items,
        public readonly ?array $totals,
        public readonly ?string $appliedCoupon,
    ) {}

    /**
     * @param  int|string  $owner  authenticated user id, or guest session id
     */
    public static function make(int|string $owner, CartService $cartService, bool $applyDiscountRate = false): self
    {
        $items = $cartService->getCartItems($owner);
        $totals = $cartService->getTotals($owner);
        $couponCode = $cartService->getAppliedCouponCode($owner);

        $discountRate = 0.0;
        if ($applyDiscountRate && ($totals?->subtotalAmount ?? 0) > 0) {
            $discountRate = $totals->discountAmount / $totals->subtotalAmount;
        }

        $mappedItems = array_map(function (CartItemDto $item) use ($discountRate) {
            $lineTotal = $item->priceAmount * $item->quantity;
            $discountedTotal = $discountRate > 0 ? (int) round($lineTotal * (1 - $discountRate)) : $lineTotal;
            $originalLineTotal = $item->originalPriceAmount * $item->quantity;

            $subtitle = $item->subtitle;
            $quantityLabel = __('web.cart.quantity').': '.$item->quantity;
            $separator = __('web.common.separator');
            $subtitle = $subtitle !== '' ? $subtitle.' '.$separator.' '.$quantityLabel : $quantityLabel;

            return [
                'id' => $item->id,
                'title' => $item->title,
                'subtitle' => $subtitle,
                'purchasableType' => $item->purchasableType,
                'purchasableId' => $item->purchasableId,
                'courseId' => $item->courseId,
                'itemType' => $item->itemType,
                'thumbnailUrl' => $item->thumbnailUrl,
                'imageUrl' => $item->imageUrl,
                'priceAmount' => $item->priceAmount,
                'originalPriceAmount' => $item->originalPriceAmount,
                'quantity' => $item->quantity,
                'lineTotal' => $lineTotal,
                'originalLineTotal' => $originalLineTotal,
                'discountedTotal' => $discountedTotal,
                'showDiscount' => $item->originalPriceAmount > $item->priceAmount,
            ];
        }, $items);

        $totalsArray = $totals ? [
            'subtotalAmount' => $totals->subtotalAmount,
            'discountAmount' => $totals->discountAmount,
            'taxAmount' => $totals->taxAmount,
            'totalAmount' => $totals->totalAmount,
            'currency' => $totals->currency,
        ] : null;

        return new self($mappedItems, $totalsArray, $couponCode);
    }
}
