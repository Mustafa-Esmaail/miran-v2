<?php

namespace App\Livewire\Home;

use App\Livewire\Concerns\UsesSectionFields;
use App\Models\Bundle;
use App\Services\CartService;
use App\Services\WishlistService;
use App\ViewModels\BundleCardViewModel;
use Illuminate\View\View;
use Livewire\Component;

class SpecialBundles extends Component
{
    use UsesSectionFields;

    public ?string $title = null;

    public ?string $subtitle = null;

    public function mount(?string $title = null, ?string $subtitle = null, ?int $sectionId = null): void
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->sectionId = $sectionId;
        $this->loadSectionFields();
    }

    public function render(CartService $cartService, WishlistService $wishlists): View
    {
        $bundles = Bundle::query()
            ->with(['category', 'discounts'])
            ->withCount('courses')
            ->where('is_active', true)
            ->latest('id')
            ->limit(3)
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

        return view('livewire.home.special-bundles', [
            'cards' => $cards,
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

    protected function hydrateSectionMeta(\App\Models\Section $section): void
    {
        $this->title = $this->title ?? $section->title;
        $this->subtitle = $this->subtitle ?? $section->subtitle;
    }
}
