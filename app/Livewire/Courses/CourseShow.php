<?php

namespace App\Livewire\Courses;

use App\Enums\CourseTemplate;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\CartService;
use App\Services\CourseReviewService;
use App\Services\Settings\SettingsService;
use App\Services\WishlistService;
use App\Support\Gtm\GtmItemFormatter;
use App\Support\S3Url;
use App\ViewModels\CourseBootcampViewModel;
use App\ViewModels\CourseCardViewModel;
use App\ViewModels\CourseShowViewModel;
use Illuminate\View\View;
use Livewire\Component;

class CourseShow extends Component
{
    public string $slug;

    public ?string $courseGroupId = null;

    public int $quantity = 1;

    public bool $isWishlisted = false;

    public string $reviewRating = '5';

    public string $reviewContent = '';

    public function mount(string $slug, CourseRepositoryInterface $courses): void
    {
        $this->slug = $slug;

        $course = $courses->findPublishedBySlug($slug);
        if ($course) {
            $item = GtmItemFormatter::fromCourse($course);
            $this->dispatch('gtm-event', name: 'view_item', data: [
                'currency' => 'SAR',
                'value' => $item['price'],
                'items' => [$item],
            ]);
        }
    }

    public function addCourseToCart(
        CartService $cartService,
        CourseRepositoryInterface $courses,
        CourseGroupRepositoryInterface $groups
    ): void {
        $course = $courses->findPublishedBySlug($this->slug);
        $groupId = (int) $this->courseGroupId;
        $quantity = max(1, $this->quantity);
        if (! $course || $groupId <= 0) {
            $this->addError('courseGroupId', __('web.course.select_group'));
            $this->dispatch('toast', message: __('web.course.select_group'));

            return;
        }

        $group = $course->groups->firstWhere('id', $groupId);
        if (! $group) {
            $this->addError('courseGroupId', __('web.course.invalid_group'));
            $this->dispatch('toast', message: __('web.course.invalid_group'));

            return;
        }
        if (! $groups->availableForEnrollment($groupId)) {
            $this->addError('courseGroupId', __('web.course.group_unavailable'));
            $this->dispatch('toast', message: __('web.course.group_unavailable'));

            return;
        }

        $cartService->addItem($this->owner(), \App\Models\CourseGroup::class, $group->id, $quantity);

        $this->dispatch('cart-updated');
        $this->redirectRoute('cart', navigate: false);
    }

    public function buyNow(
        CartService $cartService,
        CourseRepositoryInterface $courses,
        CourseGroupRepositoryInterface $groups
    ): void {
        $course = $courses->findPublishedBySlug($this->slug);
        $groupId = (int) $this->courseGroupId;
        $quantity = max(1, $this->quantity);
        if (! $course || $groupId <= 0) {
            $this->addError('courseGroupId', __('web.course.select_group'));
            $this->dispatch('toast', message: __('web.course.select_group'));

            return;
        }

        $group = $course->groups->firstWhere('id', $groupId);
        if (! $group instanceof CourseGroup) {
            $this->addError('courseGroupId', __('web.course.invalid_group'));
            $this->dispatch('toast', message: __('web.course.invalid_group'));

            return;
        }

        if (! $groups->availableForEnrollment($groupId)) {
            $this->addError('courseGroupId', __('web.course.group_unavailable'));
            $this->dispatch('toast', message: __('web.course.group_unavailable'));

            return;
        }

        $cartService->addItem($this->owner(), CourseGroup::class, $group->id, $quantity);

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

    public function toggleCourseWishlist(WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $courseId = $this->getCourseId();
        if (! $courseId) {
            return;
        }

        $this->isWishlisted = $wishlists->toggle(auth()->id(), \App\Models\Course::class, $courseId);
    }

    public function toggleWishlist(int $courseId, WishlistService $wishlists): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $added = $wishlists->toggle(auth()->id(), Course::class, $courseId);

        if ($courseId === $this->getCourseId()) {
            $this->isWishlisted = $added;
        }

        $this->dispatch('toast', message: $added
            ? __('web.course.added_to_wishlist')
            : __('web.course.removed_from_wishlist'));
    }

    public function addToCart(string $purchasableType, int $purchasableId): void
    {
        if ($purchasableType !== Course::class) {
            return;
        }

        $course = Course::query()->published()->find($purchasableId);

        if (! $course) {
            return;
        }

        session()->flash('status', __('web.course.select_group_first'));
        $this->dispatch('toast', message: __('web.course.select_group_first'));
        $this->redirectRoute('courses.show', ['slug' => $course->slug], navigate: false);
    }

    public function submitReview(CourseRepositoryInterface $courses, CourseReviewService $reviews): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $course = $courses->findPublishedBySlug($this->slug);
        abort_unless($course, 404);

        $this->validate([
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewContent' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if (! $reviews->canReview($course, auth()->user())) {
            $this->addError('reviewContent', __('web.course.review_not_allowed'));

            return;
        }

        $reviews->addReview($course, auth()->user(), (int) $this->reviewRating, $this->reviewContent);

        $this->reset('reviewRating', 'reviewContent');
        session()->flash('status', __('web.course.review_submitted'));
        $this->dispatch('toast', message: __('web.course.review_submitted'));
    }

    public function render(
        CourseRepositoryInterface $courses,
        CourseReviewService $reviews,
        SettingsService $settings,
        CartService $cartService,
        WishlistService $wishlists
    ): View {
        $course = $courses->findPublishedBySlug($this->slug);
        abort_unless($course, 404);

        $this->isWishlisted = auth()->check()
            ? $wishlists->isWishlisted(auth()->id(), \App\Models\Course::class, $course->id)
            : false;

        $sessions = $course->sessions->sortBy('start_at')->values();
        $reviewSummary = $reviews->getSummary($course);
        $viewModel = CourseShowViewModel::fromCourse($course, $sessions, $reviewSummary);

        if ($course->template?->isBootcamp()) {
            return $this->renderBootcamp($course, $viewModel, $settings, $this->relatedCourseCards($course, $courses, $cartService, $wishlists));
        }

        $summaryText = trim(strip_tags($viewModel->summary));
        $summaryText = $summaryText !== '' ? $summaryText : null;
        $isRtl = app()->getLocale() === 'ar';
        $shareUrl = route('courses.show', $viewModel->slug);
        $tabs = [
            ['id' => 'about', 'label' => __('web.course.about')],
            // ['id' => 'lessons', 'label' => __('web.course.lessons')],
            // ['id' => 'questions', 'label' => __('web.questions.tab')],
            ['id' => 'reviews', 'label' => __('web.course.reviews')],
        ];
        $instructors = collect($viewModel->instructors)->map(function (array $instructor): array {
            $name = trim((string) ($instructor['name'] ?? ''));
            $about = trim((string) ($instructor['about'] ?? ''));

            return [
                'name' => $name !== '' ? $name : null,
                'avatar' => $instructor['avatar'] ?? null,
                'about' => $about !== '' ? $about : null,
            ];
        })->all();
        $instructorNames = collect($viewModel->instructors)
            ->map(fn (array $instructor): string => trim((string) ($instructor['name'] ?? '')))
            ->filter()
            ->values();
        $instructorNamesText = $instructorNames->isNotEmpty()
            ? $instructorNames->implode(', ')
            : null;
        $sessionsPreview = array_slice($viewModel->sessions, 0, 3);
        $sessionCount = count($viewModel->sessions);
        $timelineItems = collect($course->timeline_items ?? [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $title = $this->localizedTimelineValue($item, 'title');
                $description = $this->localizedTimelineValue($item, 'description');
                $imageUrl = $this->resolveMediaUrl($item['image'] ?? null);

                if (! $title && ! $description && ! $imageUrl) {
                    return null;
                }

                return [
                    'title' => $title,
                    'description' => $description,
                    'image' => $imageUrl,
                ];
            })
            ->filter()
            ->values()
            ->all();
        $metaChips = array_filter([
            $viewModel->deliveryTypeLabel ?: null,
            $sessionCount ? $sessionCount.' '.__('web.course.sessions') : null,
            $viewModel->durationHours ? $viewModel->durationHours.' '.__('web.course.hours') : null,
        ]);
        $keyPoints = array_filter([
            $viewModel->durationHours ? __('web.course.duration_hours').': '.$viewModel->durationHours : null,
            $viewModel->daysCount ? __('web.course.days_count').': '.$viewModel->daysCount : null,
            $viewModel->deliveryTypeLabel ? __('web.course.delivery_type').': '.$viewModel->deliveryTypeLabel : null,
            $viewModel->categoryName ?: null,
        ]);
        $timelineHighlights = collect($course->timeline_highlights ?? [])
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $text = $this->localizedTimelineValue($item, 'text');
                $icon = $this->resolveMediaUrl($item['icon'] ?? null);

                if (! $text) {
                    return null;
                }

                return [
                    'text' => $text,
                    'icon' => $icon,
                ];
            })
            ->filter()
            ->values()
            ->all();
        if ($timelineHighlights === []) {
            $timelineHighlights = collect($keyPoints)
                ->map(fn (string $point): array => ['text' => $point, 'icon' => null])
                ->values()
                ->all();
        }
        $displayPrice = $viewModel->finalPrice ?? $viewModel->price;
        $hasDiscount = $viewModel->finalPrice !== null
            && $viewModel->price !== null
            && $viewModel->finalPrice < $viewModel->price;
        $sneakImages = $course->sneak_peek_image_urls;
        $ratingAverage = $viewModel->ratingAverage ?? 0;
        $ratingStars = collect(range(1, 5))->map(function (int $star) use ($ratingAverage): array {
            return [
                'fill' => $ratingAverage >= $star ? '#F6C002' : '#D3D3D3',
            ];
        })->all();
        $reviewItems = $reviews->getRecentReviews($course, 6)->map(function ($review) {
            $avatar = $review->user?->avatar_path ?? null;
            if ($avatar && ! \Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://'])) {
                $avatar = S3Url::temporary($avatar);
            }

            return [
                'name' => $review->user?->name ?? __('web.blog.unknown_author'),
                'avatar' => $avatar,
                'rating' => $review->rating,
                'content' => $review->content,
                'created_at' => $review->created_at?->diffForHumans(),
            ];
        })->all();

        $canReview = auth()->check() ? $reviews->canReview($course, auth()->user()) : false;

        $metaTitle = trim($viewModel->title.' | '.$settings->siteTitle(app()->getLocale())) ?: $viewModel->title;
        $metaDescription = $summaryText ?? \Illuminate\Support\Str::limit(strip_tags($viewModel->title), 160);
        $ogImageUrl = $viewModel->imageUrl;

        $relatedCards = $this->relatedCourseCards($course, $courses, $cartService, $wishlists);

        return view('livewire.courses.course-show', [
            'course' => $viewModel,
            'isWishlisted' => $this->isWishlisted,
            'courseGroupId' => $this->courseGroupId,
            'summaryText' => $summaryText,
            'isRtl' => $isRtl,
            'shareUrl' => $shareUrl,
            'tabs' => $tabs,
            'instructors' => $instructors,
            'instructorNamesText' => $instructorNamesText,
            'sessionsPreview' => $sessionsPreview,
            'sessionCount' => $sessionCount,
            'timelineItems' => $timelineItems,
            'metaChips' => $metaChips,
            'keyPoints' => $keyPoints,
            'timelineHighlights' => $timelineHighlights,
            'displayPrice' => $displayPrice,
            'hasDiscount' => $hasDiscount,
            'sneakImages' => $sneakImages,
            'ratingStars' => $ratingStars,
            'relatedCourses' => $relatedCards,
            'reviews' => $reviewItems,
            'canReview' => $canReview,
            'paymentMethods' => $settings->enabledPaymentMethods(),
            'tamaraPublicKey' => $this->resolveTamaraPublicKey($settings),
            'siteCurrency' => strtoupper(trim((string) $settings->getValue('site.currency', 'SAR'))) ?: 'SAR',
        ])->layout('layouts.app', [
            'fullWidth' => true,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'ogImageUrl' => $ogImageUrl,
            'whatsappMessage' => __('web.floating_contact.course_message', ['title' => $viewModel->title]),
        ]);
    }

    private function renderBootcamp(
        Course $course,
        CourseShowViewModel $viewModel,
        SettingsService $settings,
        array $relatedCourses
    ): View {
        $summaryText = trim(strip_tags($viewModel->summary));
        $hasDiscount = $viewModel->finalPrice !== null
            && $viewModel->price !== null
            && $viewModel->finalPrice < $viewModel->price;
        $whatsappPhone = $settings->whatsappPhone();
        $whatsappMessage = __('web.floating_contact.course_message', ['title' => $viewModel->title]);

        $view = $course->template === CourseTemplate::BootcampV2
            ? 'livewire.courses.course-bootcamp-v2'
            : 'livewire.courses.course-bootcamp';

        return view($view, [
            'course' => $viewModel,
            'bootcamp' => CourseBootcampViewModel::fromCourse($course),
            'isRtl' => app()->getLocale() === 'ar',
            'displayPrice' => $viewModel->finalPrice ?? $viewModel->price,
            'hasDiscount' => $hasDiscount,
            'isWishlisted' => $this->isWishlisted,
            'shareUrl' => route('courses.show', $viewModel->slug),
            'paymentMethods' => $settings->enabledPaymentMethods(),
            'relatedCourses' => $relatedCourses,
            'siteCurrency' => strtoupper(trim((string) $settings->getValue('site.currency', 'SAR'))) ?: 'SAR',
            'tamaraPublicKey' => $this->resolveTamaraPublicKey($settings),
            'whatsappUrl' => $whatsappPhone !== '' ? 'https://wa.me/'.$whatsappPhone.'?text='.rawurlencode($whatsappMessage) : null,
        ])->layout('layouts.app', [
            'fullWidth' => true,
            'metaTitle' => trim($viewModel->title.' | '.$settings->siteTitle(app()->getLocale())) ?: $viewModel->title,
            'metaDescription' => \Illuminate\Support\Str::limit($summaryText !== '' ? $summaryText : strip_tags($viewModel->title), 160),
            'ogImageUrl' => $viewModel->imageUrl,
            'whatsappMessage' => $whatsappMessage,
            'floatingContactRaised' => true,
        ]);
    }

    /**
     * Cards for the "related courses" section, with the viewer's wishlist and cart state.
     *
     * @return array<int, array<string, mixed>>
     */
    private function relatedCourseCards(
        Course $course,
        CourseRepositoryInterface $courses,
        CartService $cartService,
        WishlistService $wishlists
    ): array {
        $relatedCourses = $courses->getRelatedCourses($course, 3);
        $relatedCourses->load('groups');

        $relatedCards = CourseCardViewModel::fromCourses($relatedCourses);
        $relatedWishlistIds = auth()->check()
            ? $wishlists->getWishlistedIds(auth()->id(), Course::class)
            : [];
        $cartItems = auth()->check() ? $cartService->getCartItems(auth()->id()) : [];
        $cartGroupIds = collect($cartItems)
            ->where('itemType', 'course_group')
            ->pluck('purchasableId')
            ->filter()
            ->unique()
            ->all();
        $relatedCourseGroupIds = $relatedCourses->mapWithKeys(function (\App\Models\Course $related) {
            return [$related->id => $related->groups->pluck('id')->all()];
        });

        return collect($relatedCards)->map(function (array $card) use ($relatedWishlistIds, $cartGroupIds, $relatedCourseGroupIds) {
            $groupIds = $relatedCourseGroupIds->get($card['id'], []);
            $card['isWishlisted'] = in_array($card['id'], $relatedWishlistIds, true);
            $card['isInCart'] = count(array_intersect($groupIds, $cartGroupIds)) > 0;

            return $card;
        })->all();
    }

    private function localizedTimelineValue(array $item, string $key): ?string
    {
        $locale = app()->getLocale();
        $localizedKey = $key.'_'.$locale;

        $value = $item[$localizedKey] ?? $item[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function getCourseId(): ?int
    {
        $course = app(CourseRepositoryInterface::class)->findPublishedBySlug($this->slug);

        return $course?->id;
    }

    private function resolveTamaraPublicKey(SettingsService $settings): ?string
    {
        if (! $settings->paymentProviderIsReady('tamara')) {
            return null;
        }

        $key = trim((string) $settings->getValue('payments.tamara.init_api_key', config('services.tamara.init_api_key', '')));

        return $key !== '' ? $key : null;
    }

    private function resolveMediaUrl(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return S3Url::temporary($path);
    }
}
