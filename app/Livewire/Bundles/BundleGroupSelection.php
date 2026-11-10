<?php

namespace App\Livewire\Bundles;

use App\Models\Bundle;
use App\Models\CartItem;
use App\Models\Course;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\View\View;
use Livewire\Component;

class BundleGroupSelection extends Component
{
    public int $bundleId;

    public int $quantity = 1;

    /**
     * @var array<int, int|string>
     */
    public array $selectedGroups = [];

    public function mount(string $bundle): void
    {
        $bundleModel = Bundle::query()
            ->where('slug', $bundle)
            ->where('is_active', true)
            ->firstOrFail();

        $this->bundleId = $bundleModel->id;
        $this->hydrateExistingSelection();
    }

    public function confirmAddToCart(CartService $cartService, CourseGroupRepositoryInterface $groups): void
    {
        $bundle = $this->resolveBundle();
        $selection = $this->buildValidatedSelection($bundle, $groups);
        if ($selection === null) {
            return;
        }

        $cartService->addItem(
            $this->owner(),
            Bundle::class,
            $bundle->id,
            max(1, $this->quantity),
            ['bundle_course_groups' => $selection]
        );

        $this->dispatch('cart-updated');

        session()->flash('status', __('web.cart.added_bundle'));
        $this->dispatch('toast', message: __('web.cart.added_bundle'));
        $this->redirectRoute('cart', navigate: false);
    }

    public function render(SettingsService $settings): View
    {
        $bundle = $this->resolveBundle();
        $groups = app(CourseGroupRepositoryInterface::class);

        $courses = $bundle->courses
            ->map(function (Course $course) use ($groups): array {
                $availableGroups = $groups->getAvailableForCourse($course->id);
                $locale = app()->getLocale();

                return [
                    'id' => $course->id,
                    'slug' => $course->slug,
                    'title' => $course->getTranslation('title', $locale) ?? '',
                    'summary' => $course->getTranslation('summary', $locale) ?? '',
                    'image_url' => $course->image_url,
                    'groups' => $availableGroups->map(fn ($group): array => [
                        'id' => $group->id,
                        'title' => $group->getTranslation('title', app()->getLocale()) ?? '',
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $tamaraPublicKey = $this->resolveTamaraPublicKey($settings);
        $bundlePrice = $bundle->final_price ?? $bundle->price;

        $locale = app()->getLocale();
        $bundleTitle = $bundle->getTranslation('title', $locale) ?: '';
        $metaTitle = trim($bundleTitle.' | '.$settings->siteTitle($locale)) ?: $bundleTitle;
        $bundleSummary = $bundle->getTranslation('summary', $locale) ?: $bundle->getTranslation('description', $locale) ?: '';
        $metaDescription = trim(strip_tags($bundleSummary));
        $metaDescription = $metaDescription !== '' ? \Illuminate\Support\Str::limit($metaDescription, 160) : null;
        $ogImageUrl = $bundle->image_url;

        return view('livewire.bundles.bundle-group-selection', [
            'bundle' => $bundle,
            'courses' => $courses,
            'bundlePrice' => $bundlePrice,
            'tamaraPublicKey' => $tamaraPublicKey,
            'siteCurrency' => strtoupper(trim((string) $settings->getValue('site.currency', 'SAR'))) ?: 'SAR',
        ])->layout('layouts.app', [
            'fullWidth' => true,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'ogImageUrl' => $ogImageUrl,
        ]);
    }

    private function resolveBundle(): Bundle
    {
        return Bundle::query()
            ->with([
                'category',
                'courses' => fn ($query) => $query->published()->orderBy('id'),
            ])
            ->whereKey($this->bundleId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function hydrateExistingSelection(): void
    {
        $item = CartItem::query()
            ->whereHas('cart', function ($query) {
                if (auth()->check()) {
                    $query->where('user_id', auth()->id());
                } else {
                    $query->where('session_id', session()->getId());
                }
            })
            ->where('purchasable_type', Bundle::class)
            ->where('purchasable_id', $this->bundleId)
            ->first();

        if (! $item) {
            return;
        }

        $this->quantity = max(1, (int) $item->quantity);
        $existingSelection = data_get($item->selection_snapshot, 'bundle_course_groups', []);
        if (is_array($existingSelection)) {
            $this->selectedGroups = collect($existingSelection)
                ->mapWithKeys(fn ($value, $key): array => [(int) $key => (int) $value])
                ->all();
        }
    }

    /**
     * @return array<int, int>|null
     */
    private function buildValidatedSelection(Bundle $bundle, CourseGroupRepositoryInterface $groups): ?array
    {
        $selection = [];

        foreach ($bundle->courses as $course) {
            $courseId = (int) $course->id;
            $selectedGroupId = (int) ($this->selectedGroups[$courseId] ?? 0);
            $availableGroupIds = $groups->getAvailableForCourse($courseId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($availableGroupIds === []) {
                $this->addError('selectedGroups.'.$courseId, __('web.bundles.groups.no_available_groups'));

                continue;
            }

            if ($selectedGroupId <= 0) {
                $this->addError('selectedGroups.'.$courseId, __('web.bundles.groups.select_group_for_course'));

                continue;
            }

            if (! in_array($selectedGroupId, $availableGroupIds, true)) {
                $this->addError('selectedGroups.'.$courseId, __('web.bundles.groups.invalid_group'));

                continue;
            }

            $selection[$courseId] = $selectedGroupId;
        }

        if (count($selection) !== $bundle->courses->count()) {
            return null;
        }

        return $selection;
    }

    private function resolveTamaraPublicKey(SettingsService $settings): ?string
    {
        if (! $settings->paymentProviderIsReady('tamara')) {
            return null;
        }

        $key = trim((string) $settings->getValue('payments.tamara.init_api_key', config('services.tamara.init_api_key', '')));

        return $key !== '' ? $key : null;
    }

    /**
     * The current cart owner: the authenticated user's id, or the guest session id.
     */
    private function owner(): int|string
    {
        return auth()->check() ? auth()->id() : session()->getId();
    }
}
