<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use App\Services\WishlistService;
use App\ViewModels\CourseCardViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CoursesIndex extends Component
{
    use WithPagination;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => null],
        'upcomingOnly' => ['except' => false],
        'startFrom' => ['except' => null],
        'startTo' => ['except' => null],
        'freeOnly' => ['except' => false],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'sort' => ['except' => 'most_relevant'],
    ];

    public string $search = '';

    public ?int $categoryId = null;

    public bool $upcomingOnly = false;

    public ?string $startFrom = null;

    public ?string $startTo = null;

    public bool $freeOnly = false;

    public ?int $minPrice = null;

    public ?int $maxPrice = null;

    public string $sort = 'most_relevant';

    public int $perPage = 12;

    public bool $showFilters = false;

    public function updated($field): void
    {
        if (in_array($field, [
            'search',
            'categoryId',
            'upcomingOnly',
            'startFrom',
            'startTo',
            'freeOnly',
            'minPrice',
            'maxPrice',
            'sort',
        ], true)) {
            $this->resetPage();
        }

        if ($field === 'search' && trim($this->search) !== '') {
            $this->dispatch('gtm-event', name: 'search', data: [
                'search_term' => trim($this->search),
            ]);
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryId = null;
        $this->upcomingOnly = false;
        $this->startFrom = null;
        $this->startTo = null;
        $this->freeOnly = false;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->sort = 'most_relevant';
        $this->resetPage();
    }

    public function setPriceRange(?int $minPrice, ?int $maxPrice): void
    {
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
        $this->freeOnly = false;
        $this->resetPage();
    }

    public function render(
        CategoryRepositoryInterface $categories,
        CartService $cartService,
        WishlistService $wishlists,
        SettingsService $settings
    ): View {
        $query = Course::query()
            ->published()
            ->with('category')
            ->withCount('lessons')
            ->withCount('enrollments')
            ->withCount(['reviews as reviews_count' => function (Builder $reviewQuery) {
                $reviewQuery->approved();
            }])
            ->withAvg(['reviews as reviews_avg_rating' => function (Builder $reviewQuery) {
                $reviewQuery->approved();
            }], 'rating')
            ->with(['groups' => function ($groupQuery) {
                $this->applyGroupConstraints($groupQuery);
                $groupQuery->orderBy('start_at');
            }]);

        $this->applySearch($query);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        $this->applyPriceConstraints($query);

        if ($this->hasGroupFilters()) {
            $query->whereHas('groups', function (Builder $groupQuery) {
                $this->applyGroupConstraints($groupQuery);
            });
        }

        $this->applySorting($query);

        $paginator = $query->paginate($this->perPage);
        $cards = CourseCardViewModel::fromCourses(
            $paginator->getCollection(),
            ['discount_badge' => __('web.home.discount_badge')]
        );

        $wishlistIds = auth()->check()
            ? $wishlists->getWishlistedIds(auth()->id(), Course::class)
            : [];
        $cartItems = auth()->check() ? $cartService->getCartItems(auth()->id()) : [];
        $cartGroupIds = collect($cartItems)
            ->where('itemType', 'course_group')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();

        $courseGroupIds = $paginator->getCollection()->mapWithKeys(function (Course $course) {
            return [$course->id => $course->groups->pluck('id')->all()];
        });

        $cards = collect($cards)->map(function (array $card) use ($wishlistIds, $cartGroupIds, $courseGroupIds) {
            $groupIds = $courseGroupIds->get($card['id'], []);
            $isInCart = count(array_intersect($groupIds, $cartGroupIds)) > 0;

            $card['isWishlisted'] = in_array($card['id'], $wishlistIds, true);
            $card['isInCart'] = $isInCart;

            return $card;
        })->all();

        $title = match (request()->route()->getName()) {
            'courses.search' => __('web.courses.search_title'),
            default => __('web.courses.explore_title'),
        };

        $metaTitle = trim($title.' | '.$settings->siteTitle(app()->getLocale())) ?: $title;
        $metaDescription = __('web.courses.subtitle');

        $sortOptions = $this->getSortOptions();

        return view('livewire.courses.courses-index', [
            'title' => $title,
            'cards' => $cards,
            'paginator' => $paginator,
            'categories' => $categories->getActiveOrderedByName(app()->getLocale()),
            'activeFilters' => $this->getActiveFiltersCount(),
            'sortOptions' => $sortOptions,
            'currentSortLabel' => $sortOptions[$this->sort] ?? __('web.filters.sort.most_relevant'),
            'priceAll' => $this->isPriceAll(),
            'priceUnder' => $this->isPriceUnder(),
            'priceAbove' => $this->isPriceAbove(),
        ])->layout('layouts.app', [
            'fullWidth' => true,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'canonicalUrl' => route('courses.index'),
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

    protected function applySearch(Builder $query): void
    {
        $search = trim($this->search);
        if ($search === '') {
            return;
        }

        $query->matchingSearch($search);
    }

    protected function applyGroupConstraints($query): void
    {
        $query->open();

        if ($this->upcomingOnly) {
            $query->whereDate('start_at', '>=', Carbon::today());
        }

        if ($this->startFrom) {
            $query->whereDate('start_at', '>=', $this->startFrom);
        }

        if ($this->startTo) {
            $query->whereDate('start_at', '<=', $this->startTo);
        }
    }

    protected function applyPriceConstraints(Builder $query): void
    {
        if ($this->freeOnly) {
            $query->where('is_free', true);
        }

        if ($this->minPrice !== null && $this->minPrice !== '') {
            $query->where('price', '>=', $this->minPrice);
        }

        if ($this->maxPrice !== null && $this->maxPrice !== '') {
            $query->where('price', '<=', $this->maxPrice);
        }
    }

    protected function applySorting(Builder $query): void
    {
        if ($this->sort === 'price_low') {
            $query->orderBy('price');

            return;
        }

        if ($this->sort === 'price_high') {
            $query->orderByDesc('price');

            return;
        }

        if ($this->sort === 'newest') {
            $query->latest();

            return;
        }

        if ($this->sort === 'rating') {
            $query->latest();

            return;
        }

        if ($this->sort === 'most_relevant') {
            $query->orderBy('sort_order')->latest();

            return;
        }

        if ($this->sort === 'recommended') {
            $query->orderBy('sort_order')->latest();

            return;
        }

        if ($this->sort === 'start_soonest') {
            if (! $this->hasGroupFilters()) {
                $query->whereHas('groups', function (Builder $groupQuery) {
                    $this->applyGroupConstraints($groupQuery);
                });
            }
            $query->withMin(['groups as next_group_start_at' => function (Builder $groupQuery) {
                $this->applyGroupConstraints($groupQuery);
            }], 'start_at')
                ->orderBy('next_group_start_at');

            return;
        }

        $query->latest();
    }

    protected function hasGroupFilters(): bool
    {
        return $this->upcomingOnly
            || $this->startFrom
            || $this->startTo;
    }

    protected function getActiveFiltersCount(): int
    {
        $filters = [
            'search' => $this->search !== '',
            'categoryId' => $this->categoryId,
            'upcomingOnly' => $this->upcomingOnly,
            'startFrom' => $this->startFrom,
            'startTo' => $this->startTo,
            'freeOnly' => $this->freeOnly,
            'minPrice' => $this->minPrice !== null && $this->minPrice !== '',
            'maxPrice' => $this->maxPrice !== null && $this->maxPrice !== '',
            'sort' => $this->sort !== 'most_relevant',
        ];

        return count(array_filter($filters));
    }

    protected function getSortOptions(): array
    {
        return [
            'newest' => __('web.filters.sort.newest'),
            'most_relevant' => __('web.filters.sort.most_relevant'),
            'recommended' => __('web.filters.sort.recommended'),
            'rating' => __('web.filters.sort.rating'),
        ];
    }

    protected function isPriceAll(): bool
    {
        return $this->minPrice === null && $this->maxPrice === null;
    }

    protected function isPriceUnder(): bool
    {
        return $this->maxPrice !== null
            && (int) $this->maxPrice === 500
            && ($this->minPrice === null || (int) $this->minPrice === 0);
    }

    protected function isPriceAbove(): bool
    {
        return $this->minPrice !== null
            && (int) $this->minPrice >= 500
            && $this->maxPrice === null;
    }
}
