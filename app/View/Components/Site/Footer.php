<?php

namespace App\View\Components\Site;

use App\Models\MenuItem;
use App\Repositories\Contracts\MenuRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Footer extends Component
{
    public bool $isRtl;

    public string $addressValue;

    public string $year;

    public array $footerPolicyLinks;

    public array $socialLinks;

    public array $browseFallback;

    public array $categoryFallback;

    public array $supportFallback;

    public array $footerBrowseLinks;

    public array $footerCategoryLinks;

    public array $footerCertificationCards;

    public function __construct(
        public ?string $footerAddressLabel = null,
        public ?string $footerAddressValue = null,
        public ?string $footerHours = null,
        public ?string $footerContactLabel = null,
        public ?string $footerEmail = null,
        public ?string $footerWhatsapp = null,
        public ?string $footerPoliciesLabel = null,
        array $footerPolicyLinks = [],
        public ?string $paymentsStripUrl = null,
        public array $paymentMethodLogos = [],
        array $footerCertificationCards = [],
        array $socialLinks = [],
    ) {
        $this->isRtl = app()->getLocale() === 'ar';
        $this->addressValue = trim((string) $this->footerAddressValue);
        $this->year = (string) date('Y');
        $this->footerPolicyLinks = is_array($footerPolicyLinks) ? $footerPolicyLinks : [];
        $this->footerCertificationCards = is_array($footerCertificationCards) ? $footerCertificationCards : [];
        $this->socialLinks = collect($socialLinks)->map(function (array $link) {
            $link['icon'] = $link['icon'] ?? 'linkedin';

            return $link;
        })->all();

        $this->browseFallback = [
            __('web.footer.featured_courses'),
            __('web.footer.featured_categories'),
            __('web.footer.category_business'),
            __('web.footer.category_it'),
            __('web.footer.category_design'),
        ];
        $this->categoryFallback = [
            __('web.footer.category_development'),
            __('web.footer.category_work_skills'),
            __('web.footer.category_self_development'),
            __('web.footer.category_finance'),
            __('web.footer.category_visual_arts'),
        ];
        $this->supportFallback = [
            __('web.footer.contact'),
            __('web.footer.help'),
            __('web.footer.about'),
        ];

        $menuRepository = app(MenuRepositoryInterface::class);
        $this->footerBrowseLinks = $this->buildMenuLinks(
            $menuRepository->getActiveItemsForLocation('footer_browse')
        );
        $this->footerCategoryLinks = $this->buildMenuLinks(
            $menuRepository->getActiveItemsForLocation('footer_category')
        );
        $footerPolicyMenuLinks = $this->buildMenuLinks(
            $menuRepository->getActiveItemsForLocation('footer_policies')
        );
        if ($footerPolicyMenuLinks !== []) {
            $this->footerPolicyLinks = collect($footerPolicyMenuLinks)->map(function (array $link): array {
                return [
                    'label' => $link['label'],
                    'url' => $link['href'],
                ];
            })->all();
        }

        $menuSocialLinks = $this->buildSocialLinks(
            $menuRepository->getActiveItemsForLocation('footer_social')
        );
        if ($menuSocialLinks !== []) {
            $this->socialLinks = $menuSocialLinks;
        }
    }

    private function buildSocialLinks(Collection $items): array
    {
        $locale = app()->getLocale();

        return $items->map(function (MenuItem $item) use ($locale) {
            $label = $item->getTranslation('label', $locale)
                ?: $item->getTranslation('label', 'en')
                ?: $item->getTranslation('label', 'ar');

            return [
                'label'   => $label,
                'href'    => $item->url ?? '#',
                'icon'    => $item->icon ?? 'linkedin',
                'new_tab' => (bool) $item->open_in_new_tab,
            ];
        })->filter(fn (array $link) => $link['href'] !== '#')->values()->all();
    }

    private function buildMenuLinks(Collection $items): array
    {
        $locale = app()->getLocale();

        return $items->map(function (MenuItem $item) use ($locale) {
            $label = $item->getTranslation('label', $locale)
                ?: $item->getTranslation('label', 'en')
                ?: $item->getTranslation('label', 'ar');

            $href = '#';
            if ($item->type === 'page' && $item->page && $item->page->is_active) {
                $href = route('pages.show', $item->page->slug);
            } elseif ($item->type === 'custom' && $item->url) {
                $href = $item->url;
            }

            return [
                'label' => $label,
                'href' => $href,
                'new_tab' => (bool) $item->open_in_new_tab,
            ];
        })->filter(function (array $link) {
            return $link['label'] !== '' && $link['href'] !== '#';
        })->values()->all();
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
