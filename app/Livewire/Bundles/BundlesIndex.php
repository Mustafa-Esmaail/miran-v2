<?php

namespace App\Livewire\Bundles;

use App\Models\Bundle;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use App\Services\WishlistService;
use App\ViewModels\BundleCardViewModel;
use Illuminate\View\View;
use Livewire\Component;

class BundlesIndex extends Component
{
    public function render(CartService $cartService, WishlistService $wishlists, SettingsService $settings): View
    {
        $bundles = Bundle::query()
            ->with(['category', 'discounts'])
            ->withCount('courses')
            ->where('is_active', true)
            ->latest('id')
            ->get();

        $cards = BundleCardViewModel::fromBundles($bundles);

        $cartItems = auth()->check()
            ? $cartService->getCartItems(auth()->id())
            : [];
        $cartBundleIds = collect($cartItems)
            ->where('itemType', 'bundle')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();

        $cards = collect($cards)->map(function (array $card) use ($cartBundleIds) {
            $card['isInCart'] = in_array($card['id'], $cartBundleIds, true);

            return $card;
        })->all();

        $wishlistIds = auth()->check()
            ? $wishlists->getWishlistedIds(auth()->id(), Bundle::class)
            : [];
        $cards = collect($cards)->map(function (array $card) use ($wishlistIds) {
            $card['isWishlisted'] = in_array($card['id'], $wishlistIds, true);

            return $card;
        })->all();

        $title = __('web.bundles.title');
        $metaTitle = trim($title.' | '.$settings->siteTitle(app()->getLocale())) ?: $title;
        $metaDescription = __('web.bundles.subtitle');

        return view('livewire.bundles.bundles-index', [
            'cards' => $cards,
        ])->layout('layouts.app', [
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
        ]);
    }

    public function addToCart(string $purchasableType, int $purchasableId): void
    {
        if ($purchasableType !== Bundle::class) {
            return;
        }

        $bundle = Bundle::find($purchasableId);
        if (! $bundle) {
            return;
        }

        $this->redirectRoute('bundles.select-groups', ['bundle' => $bundle], navigate: false);
    }

    public function buyNow(string $purchasableType, int $purchasableId): void
    {
        if ($purchasableType !== Bundle::class) {
            return;
        }

        $bundle = Bundle::find($purchasableId);
        if (! $bundle) {
            return;
        }

        $this->redirectRoute('bundles.select-groups', ['bundle' => $bundle], navigate: false);
    }

    public function toggleWishlist(int $bundleId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Bundle::class, $bundleId);
        if (! $added) {
            $this->dispatch('toast', message: __('web.course.removed_from_wishlist'));

            return;
        }

        $this->dispatch('toast', message: __('web.course.added_to_wishlist'));
    }
}
