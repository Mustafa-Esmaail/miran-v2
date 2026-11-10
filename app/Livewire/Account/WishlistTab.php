<?php

namespace App\Livewire\Account;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\WishlistItem;
use App\Services\CartService;
use App\Services\WishlistService;
use App\ViewModels\BundleCardViewModel;
use App\ViewModels\CourseCardViewModel;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class WishlistTab extends Component
{
    use WithPagination;

    public int $perPage = 9;

    public function toggleWishlist(int $courseId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Course::class, $courseId);
        if (! $added) {
            $this->dispatch('toast', message: __('web.course.removed_from_wishlist'));
            $this->resetPage();

            return;
        }

        $this->dispatch('toast', message: __('web.course.added_to_wishlist'));
    }

    public function toggleBundleWishlist(int $bundleId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Bundle::class, $bundleId);
        if (! $added) {
            $this->dispatch('toast', message: __('web.course.removed_from_wishlist'));
            $this->resetPage();

            return;
        }

        $this->dispatch('toast', message: __('web.course.added_to_wishlist'));
    }

    public function addToCart(CartService $cartService, string $purchasableType, int $purchasableId): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        if ($purchasableType === Bundle::class) {
            $bundle = Bundle::find($purchasableId);
            if ($bundle) {
                $this->redirectRoute('bundles.select-groups', ['bundle' => $bundle], navigate: false);
            }

            return;
        }

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

        session()->flash('status', __('web.course.select_group_first'));
        $this->dispatch('toast', message: __('web.course.select_group_first'));
        $this->redirectRoute('courses.show', ['slug' => $course->slug], navigate: false);
    }

    public function buyNow(CartService $cartService, string $purchasableType, int $purchasableId): void
    {
        if ($purchasableType === Bundle::class) {
            $bundle = Bundle::find($purchasableId);
            if ($bundle) {
                $this->redirectRoute('bundles.select-groups', ['bundle' => $bundle], navigate: false);
            }

            return;
        }

        if ($purchasableType !== Course::class) {
            return;
        }

        $course = Course::query()
            ->with(['groups' => fn ($query) => $query->open()->orderBy('start_at')])
            ->published()
            ->find($purchasableId);

        if (! $course) {
            return;
        }

        if ($course->groups->count() !== 1) {
            session()->flash('status', __('web.course.select_group_first'));
            $this->dispatch('toast', message: __('web.course.select_group_first'));
            $this->redirectRoute('courses.show', ['slug' => $course->slug], navigate: false);

            return;
        }

        $group = $course->groups->first();

        if (! $group instanceof CourseGroup) {
            return;
        }

        $cartService->addItem(auth()->id(), CourseGroup::class, $group->id);

        $this->dispatch('cart-updated');
        $this->redirectRoute('cart', navigate: false);
    }

    public function render(CartService $cartService): View
    {
        $courseIds = WishlistItem::query()
            ->where('user_id', auth()->id())
            ->where('wishable_type', Course::class)
            ->pluck('wishable_id')
            ->all();

        $bundleIds = WishlistItem::query()
            ->where('user_id', auth()->id())
            ->where('wishable_type', Bundle::class)
            ->pluck('wishable_id')
            ->all();

        $courses = Course::query()
            ->published()
            ->with(['category', 'discounts', 'groups'])
            ->withCount(['lessons', 'enrollments'])
            ->withCount(['reviews as reviews_count' => function ($reviewQuery) {
                $reviewQuery->approved();
            }])
            ->withAvg(['reviews as reviews_avg_rating' => function ($reviewQuery) {
                $reviewQuery->approved();
            }], 'rating')
            ->whereIn('id', $courseIds)
            ->get();

        $courseCards = CourseCardViewModel::fromCourses(
            $courses,
            ['discount_badge' => __('web.home.discount_badge')]
        );

        $bundles = Bundle::query()
            ->with(['category', 'discounts'])
            ->withCount('courses')
            ->whereIn('id', $bundleIds)
            ->get();

        $bundleCards = BundleCardViewModel::fromBundles($bundles);

        $cartItems = auth()->check()
            ? $cartService->getCartItems(auth()->id())
            : [];
        $cartGroupIds = collect($cartItems)
            ->where('itemType', 'course_group')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();
        $cartBundleIds = collect($cartItems)
            ->where('itemType', 'bundle')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();

        $courseGroupIds = $courses->mapWithKeys(function (Course $course) {
            return [$course->id => $course->groups->pluck('id')->all()];
        });

        $courseCards = collect($courseCards)->map(function (array $card) use ($cartGroupIds, $courseGroupIds) {
            $groupIds = $courseGroupIds->get($card['id'], []);
            $card['isWishlisted'] = true;
            $card['isInCart'] = count(array_intersect($groupIds, $cartGroupIds)) > 0;

            return $card;
        })->all();

        $bundleCards = collect($bundleCards)->map(function (array $card) use ($cartBundleIds) {
            $card['isWishlisted'] = true;
            $card['isInCart'] = in_array($card['id'], $cartBundleIds, true);

            return $card;
        })->all();

        return view('livewire.account.wishlist-tab', [
            'courseCards' => $courseCards,
            'bundleCards' => $bundleCards,
        ])->layout('layouts.app');
    }
}
