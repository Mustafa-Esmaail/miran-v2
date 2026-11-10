<?php

namespace App\Livewire\Cart;

use App\Models\Course;
use App\Services\CartService;
use App\Services\WishlistService;
use App\Support\Gtm\GtmEventQueue;
use App\Support\Gtm\GtmItemFormatter;
use App\ViewModels\CartViewModel;
use App\ViewModels\CourseCardViewModel;
use Illuminate\View\View;
use Livewire\Component;

class CartPanel extends Component
{
    public string $couponCode = '';

    public ?string $message = null;

    public ?string $appliedCoupon = null;

    /**
     * @var array<int, int>
     */
    public array $quantities = [];

    protected $listeners = ['cart-updated' => 'refreshCart'];

    public function mount(CartService $cartService): void
    {
        $vm = CartViewModel::make($this->owner(), $cartService, false);

        if ($vm->items !== []) {
            $this->dispatch('gtm-event', name: 'view_cart', data: [
                'currency' => $vm->totals['currency'] ?? 'SAR',
                'value' => GtmItemFormatter::valueOf($vm->items),
                'items' => GtmItemFormatter::fromCartItems($vm->items),
            ]);
        }
    }

    public function applyCoupon(CartService $cartService): void
    {
        $this->resetErrorBag();
        $this->couponCode = strtoupper(trim($this->couponCode));
        if ($this->couponCode === '') {
            $this->addError('couponCode', __('web.cart.coupon_required'));

            return;
        }

        $reason = $cartService->applyCoupon($this->owner(), $this->couponCode);
        $this->message = match ($reason) {
            null => __('web.cart.coupon_applied'),
            'not_applicable' => __('web.cart.coupon_not_applicable'),
            default => __('web.cart.coupon_invalid'),
        };

        if ($reason === null) {
            $totals = $cartService->getTotals($this->owner());
            $this->dispatch('gtm-event', name: 'apply_coupon', data: [
                'coupon' => $this->couponCode,
                'currency' => $totals?->currency ?? 'SAR',
                'value' => $totals?->discountAmount ?? 0,
            ]);
        }

        $this->dispatch('toast', message: $this->message);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId, CartService $cartService): void
    {
        $removedItem = collect($cartService->getCartItems($this->owner()))
            ->first(fn ($item) => $item->id === $itemId);

        $cartService->removeItem($this->owner(), $itemId);
        $this->flashCouponAutoRemoval($cartService);

        if ($removedItem) {
            $this->dispatch('gtm-event', name: 'remove_from_cart', data: [
                'currency' => 'SAR',
                'value' => $removedItem->priceAmount * $removedItem->quantity,
                'items' => [GtmItemFormatter::fromCartItem([
                    'purchasableId' => $removedItem->purchasableId,
                    'courseId' => $removedItem->courseId,
                    'title' => $removedItem->title,
                    'itemType' => $removedItem->itemType,
                    'priceAmount' => $removedItem->priceAmount,
                    'originalPriceAmount' => $removedItem->originalPriceAmount,
                    'quantity' => $removedItem->quantity,
                ])],
            ]);
        }

        $this->dispatch('cart-updated');
    }

    public function removeCoupon(CartService $cartService): void
    {
        $previousCode = $cartService->getAppliedCouponCode($this->owner());
        $cartService->removeCoupon($this->owner());
        $this->couponCode = '';
        $this->message = null;

        if ($previousCode) {
            $this->dispatch('gtm-event', name: 'remove_coupon', data: ['coupon' => $previousCode]);
        }

        $this->dispatch('toast', message: __('web.checkout.remove_coupon'));
        $this->dispatch('cart-updated');
    }

    public function updateCart(CartService $cartService): void
    {
        foreach ($this->quantities as $itemId => $quantity) {
            $cartService->updateQuantity($this->owner(), (int) $itemId, (int) $quantity);
        }

        $this->flashCouponAutoRemoval($cartService);
        $this->dispatch('cart-updated');
    }

    public function updatedQuantities(int $value, string $key): void
    {
        $itemId = (int) $key;
        $quantity = max(1, $value);
        $this->quantities[$itemId] = $quantity;

        $cartService = app(CartService::class);
        $cartService->updateQuantity($this->owner(), $itemId, $quantity);
        $this->flashCouponAutoRemoval($cartService);
        $this->dispatch('cart-updated');
    }

    public function increaseQuantity(int $itemId, int $max = 99): void
    {
        $current = (int) ($this->quantities[$itemId] ?? 1);
        $this->quantities[$itemId] = min($max, $current + 1);
        $this->updatedQuantities($this->quantities[$itemId], (string) $itemId);
    }

    public function decreaseQuantity(int $itemId): void
    {
        $current = (int) ($this->quantities[$itemId] ?? 1);
        $this->quantities[$itemId] = max(1, $current - 1);
        $this->updatedQuantities($this->quantities[$itemId], (string) $itemId);
    }

    public function addToCart(string $purchasableType, int $purchasableId, CartService $cartService): void
    {
        if ($purchasableType !== Course::class) {
            return;
        }

        $course = Course::query()
            ->with('groups')
            ->published()
            ->find($purchasableId);

        if (! $course) {
            return;
        }

        $group = $course->groups->sortBy('start_at')->first();
        if (! $group) {
            return;
        }

        $cartService->addItem($this->owner(), \App\Models\CourseGroup::class, $group->id);
        $this->flashCouponAutoRemoval($cartService);

        foreach (GtmEventQueue::pull() as $queued) {
            $this->dispatch('gtm-event', name: $queued['event'], data: $queued['data']);
        }

        $this->dispatch('cart-updated');
        session()->flash('status', __('web.cart.added'));
        $this->dispatch('toast', message: __('web.cart.added'));
    }

    public function toggleWishlist(int $courseId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect('/login');

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Course::class, $courseId);
        if (! $added) {
            $this->dispatch('toast', message: __('web.course.removed_from_wishlist'));

            return;
        }

        $this->dispatch('toast', message: __('web.course.added_to_wishlist'));
    }

    public function refreshCart(): void
    {
        $this->dispatch('$refresh');
    }

    public function render(CartService $cartService, WishlistService $wishlists): View
    {
        $vm = CartViewModel::make($this->owner(), $cartService, false);
        $displayItems = $vm->items;
        $this->syncQuantities($vm->items);
        $currency = $vm->totals['currency'] ?? 'SAR';
        $itemDiscountTotal = collect($displayItems)->sum(function (array $item) {
            $original = $item['originalPriceAmount'] ?? $item['priceAmount'];
            $perItemDiscount = max(0, $original - $item['priceAmount']);

            return $perItemDiscount * $item['quantity'];
        });

        $couponDiscountTotal = $vm->totals['discountAmount'] ?? 0;
        $displaySubtotal = $vm->totals['subtotalAmount'] ?? collect($displayItems)->sum('lineTotal');
        $displayTotal = $vm->totals['totalAmount'] ?? collect($displayItems)->sum('lineTotal');
        $suggested = Course::query()
            ->published()
            ->with(['category', 'groups'])
            ->withCount('lessons')
            ->withCount('enrollments')
            ->withCount(['reviews as reviews_count' => function ($reviewQuery) {
                $reviewQuery->approved();
            }])
            ->withAvg(['reviews as reviews_avg_rating' => function ($reviewQuery) {
                $reviewQuery->approved();
            }], 'rating')
            ->latest()
            ->limit(3)
            ->get();
        $suggestedCards = CourseCardViewModel::fromCourses($suggested, [
            'discount_badge' => __('web.home.discount_badge'),
        ]);
        $wishlistIds = auth()->check()
            ? $wishlists->getWishlistedIds(auth()->id(), Course::class)
            : [];
        $cartGroupIds = collect($vm->items)
            ->where('itemType', 'course_group')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();

        $courseGroupIds = $suggested->mapWithKeys(function (Course $course) {
            return [$course->id => $course->groups->pluck('id')->all()];
        });

        $suggestedCards = collect($suggestedCards)->map(function (array $card) use ($wishlistIds, $cartGroupIds, $courseGroupIds) {
            $groupIds = $courseGroupIds->get($card['id'], []);
            $card['isWishlisted'] = in_array($card['id'], $wishlistIds, true);
            $card['isInCart'] = count(array_intersect($groupIds, $cartGroupIds)) > 0;

            return $card;
        })->all();

        return view('livewire.cart.cart-panel', [
            'vm' => $vm,
            'displayItems' => $displayItems,
            'displaySubtotal' => $displaySubtotal,
            'displayTotal' => $displayTotal,
            'suggestedCards' => $suggestedCards,
            'currency' => $currency,
            'itemDiscountTotal' => $itemDiscountTotal,
            'couponDiscountTotal' => $couponDiscountTotal,
            'appliedCoupon' => $vm->appliedCoupon,
        ])->layout('layouts.app');
    }

    /**
     * @param  array<int, array{id:int, quantity:int}>  $items
     */
    private function syncQuantities(array $items): void
    {
        $itemIds = collect($items)->pluck('id')->all();

        foreach ($items as $item) {
            if (! array_key_exists($item['id'], $this->quantities)) {
                $this->quantities[$item['id']] = (int) $item['quantity'];
            }
        }

        foreach (array_keys($this->quantities) as $itemId) {
            if (! in_array($itemId, $itemIds, true)) {
                unset($this->quantities[$itemId]);
            }
        }
    }

    /**
     * Surfaces a toast when a cart edit made the applied coupon no longer qualify
     * (e.g. dropped below min_items_count), instead of leaving the coupon silently
     * removed with no explanation.
     */
    private function flashCouponAutoRemoval(CartService $cartService): void
    {
        $reason = $cartService->consumeCouponRemovalReason($this->owner());
        if ($reason === null) {
            return;
        }

        $this->message = __('web.cart.coupon_removed_cart_changed');
        $this->dispatch('toast', message: $this->message);
    }

    /**
     * The current cart owner: the authenticated user's id, or the guest session id.
     */
    private function owner(): int|string
    {
        return auth()->check() ? auth()->id() : session()->getId();
    }
}
