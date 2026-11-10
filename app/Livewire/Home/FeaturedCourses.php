<?php

namespace App\Livewire\Home;

use App\Livewire\Concerns\UsesSectionFields;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\CartService;
use App\Services\WishlistService;
use App\ViewModels\CourseCardViewModel;
use App\ViewModels\FeaturedCoursesViewModel;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FeaturedCourses extends Component
{
    use UsesSectionFields;

    protected $queryString = [
        'categoryId' => ['except' => null],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'upcomingOnly' => ['except' => false],
    ];

    #[Locked]
    public ?string $title = null;

    #[Locked]
    public ?string $subtitle = null;

    public ?int $categoryId = null;

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    public bool $upcomingOnly = false;

    public function mount(?string $title = null, ?string $subtitle = null, ?int $sectionId = null): void
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->sectionId = $sectionId;
        $this->loadSectionFields();
    }

    public function applyFilters(): void
    {
        $this->normalizePriceRange();
    }

    public function clearFilters(): void
    {
        $this->categoryId = null;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->upcomingOnly = false;
    }

    public function render(
        CourseRepositoryInterface $courses,
        CartService $cartService,
        WishlistService $wishlists
    ): View {
        $locale = app()->getLocale();

        $categoryId = $this->categoryId;

        $courses = $courses->getFeaturedCourses([
            'category_id' => $categoryId,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'upcoming_only' => $this->upcomingOnly,
        ], 3);
        $courses->load('groups');

        $getText = fn (string $key, string $fallback = '') => $this->fieldText($key, $locale, $fallback);
        $vm = FeaturedCoursesViewModel::make($this->title, $this->subtitle, $getText);
        $cards = CourseCardViewModel::fromCourses($courses, ['discount_badge' => $vm->discountLabel], [
            'meta_right' => $vm->quickViewHint,
        ]);
        $wishlistIds = auth()->check()
            ? $wishlists->getWishlistedIds(auth()->id(), Course::class)
            : [];

        $cartItems = auth()->check()
            ? $cartService->getCartItems(auth()->id())
            : [];
        $cartGroupIds = collect($cartItems)
            ->where('itemType', 'course_group')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();

        $courseGroupIds = $courses->mapWithKeys(function (Course $course) {
            return [$course->id => $course->groups->pluck('id')->all()];
        });

        $cards = collect($cards)->map(function (array $card) use ($wishlistIds, $cartGroupIds, $courseGroupIds) {
            $groupIds = $courseGroupIds->get($card['id'], []);
            $card['isWishlisted'] = in_array($card['id'], $wishlistIds, true);
            $card['isInCart'] = count(array_intersect($groupIds, $cartGroupIds)) > 0;

            return $card;
        })->all();

        return view('livewire.home.featured-courses', [
            'vm' => $vm,
            'cards' => $cards,
            'buttonLabel' => $this->fieldText('button_text', $locale, __('web.home.view_courses')),
            'buttonUrl' => $this->fieldValue('button_url', route('courses.index')),
        ]);
    }

    public function addToCart(CartService $cartService, string $purchasableType, int $purchasableId): void
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

        session()->flash('status', __('web.course.select_group_first'));
        $this->dispatch('toast', message: __('web.course.select_group_first'));
        $this->redirectRoute('courses.show', ['slug' => $course->slug], navigate: false);
    }

    public function buyNow(CartService $cartService, string $purchasableType, int $purchasableId): void
    {
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

        $cartService->addItem($this->owner(), CourseGroup::class, $group->id);

        $this->dispatch('cart-updated');
        $this->redirectRoute('cart', navigate: false);
    }

    /**
     * The current cart owner: the authenticated user's id, or the guest session id.
     */
    private function owner(): int|string
    {
        return auth()->check() ? auth()->id() : session()->getId();
    }

    public function toggleWishlist(int $courseId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Course::class, $courseId);
        if (! $added) {
            $this->dispatch('toast', message: __('web.course.removed_from_wishlist'));

            return;
        }

        $this->dispatch('toast', message: __('web.course.added_to_wishlist'));
    }

    protected function normalizePriceRange(): void
    {
        if ($this->minPrice !== null && $this->maxPrice !== null && $this->minPrice > $this->maxPrice) {
            $swap = $this->minPrice;
            $this->minPrice = $this->maxPrice;
            $this->maxPrice = $swap;
        }
    }

    protected function hydrateSectionMeta(\App\Models\Section $section): void
    {
        $this->title = $this->title ?? $section->title;
        $this->subtitle = $this->subtitle ?? $section->subtitle;
    }
}
