<?php

namespace App\View\Composers;

use App\Services\Settings\MenuService;
use App\Services\Settings\SettingsService;
use App\Support\S3Url;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly MenuService $menuService
    ) {}

    public function compose(View $view): void
    {
        $locale = app()->getLocale();
        $siteTitle = $this->settingsService->siteTitle($locale) ?: __('web.layout.title');
        $metaTitle = $this->settingsService->getTranslatable('seo.meta_title', $locale) ?: $siteTitle;
        $metaDescription = $this->settingsService->getTranslatable('seo.meta_description', $locale, __('web.layout.meta_description'));
        $canonicalUrl = url()->current();
        $logoUrl = $this->settingsService->logoUrl();
        $faviconUrl = $this->settingsService->faviconUrl()
            ?: asset('img/logo.png');
        $ogImageUrl = $this->settingsService->ogImageUrl()
            ?: asset('img/logo.png');
        $paymentsStripUrl = null;
        $paymentMethodLogos = $this->settingsService->paymentMethodLogos();
        $topbarLeft = $this->settingsService->getTranslatable(
            'topbar.left_text',
            $locale,
            __('web.layout.commercial_registration')
        );
        $topbarCenter = $this->settingsService->getTranslatable(
            'topbar.center_text',
            $locale,
            __('web.home.topbar_message')
        );
        $topbarRight = $this->settingsService->getTranslatable(
            'topbar.right_text',
            $locale,
            __('web.layout.licensing_number')
        );

        $defaultNavLinks = [
            ['label' => __('web.nav.home'), 'url' => route('home')],
            ['label' => __('web.nav.about'), 'url' => route('home').'#about'],
            ['label' => __('web.nav.courses'), 'url' => route('courses.index')],
            ['label' => __('web.nav.contact_request'), 'url' => route('home').'#contact'],
            ['label' => __('web.nav.corporate_training'), 'url' => route('home').'#corporate'],
            ['label' => __('web.nav.complaints'), 'url' => route('home').'#complaints'],
        ];
        $navLinksRaw = $this->settingsService->getValue('navbar.links', $defaultNavLinks);
        $navLinksRaw = is_array($navLinksRaw) ? $navLinksRaw : $defaultNavLinks;
        $currentPath = trim(parse_url(url()->current(), PHP_URL_PATH) ?? '', '/');
        $navLinks = collect($navLinksRaw)->map(function ($link) use ($locale, $currentPath) {
            $label = $link['label'] ?? ($locale === 'ar' ? ($link['label_ar'] ?? '') : ($link['label_en'] ?? ''));
            if ($label === '') {
                $label = $locale === 'ar'
                    ? ($link['label_ar'] ?? $link['label_en'] ?? '')
                    : ($link['label_en'] ?? $link['label_ar'] ?? '');
            }
            $url = $link['url'] ?? $link['href'] ?? '#';
            $linkPath = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $isActive = $linkPath !== '' && $currentPath === $linkPath;
            if ($url === route('home')) {
                $isActive = request()->routeIs('home');
            }
            if (str_contains($url, route('courses.index'))) {
                $isActive = request()->routeIs('courses.*');
            }

            return [
                'label' => $label,
                'href' => $url,
                'active' => $isActive,
                'new_tab' => (bool) ($link['new_tab'] ?? false),
                'children' => [],
            ];
        })->all();

        $menuItems = $this->menuService->getHeaderMenuItems();
        $menuLinks = [];
        if ($menuItems->isNotEmpty()) {
            $buildMenu = function ($items) use (&$buildMenu, $locale, $currentPath) {
                return $items->map(function ($item) use (&$buildMenu, $locale, $currentPath) {
                    $label = $item->getTranslation('label', $locale)
                        ?: $item->getTranslation('label', 'en')
                        ?: $item->getTranslation('label', 'ar');
                    $href = '#';
                    if ($item->type === 'page' && $item->page && $item->page->is_active) {
                        $href = route('pages.show', $item->page->slug);
                    } elseif ($item->type === 'custom' && $item->url) {
                        $href = $item->url;
                    }

                    $children = $item->children ? $buildMenu($item->children) : collect();
                    $linkPath = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');
                    $isActive = $linkPath !== '' && $currentPath === $linkPath;
                    if ($href === route('home')) {
                        $isActive = request()->routeIs('home');
                    }
                    if (str_contains($href, route('courses.index'))) {
                        $isActive = request()->routeIs('courses.*');
                    }

                    $childrenArray = $children->values()->all();
                    if (! $isActive && $children->firstWhere('active', true)) {
                        $isActive = true;
                    }

                    return [
                        'label' => $label,
                        'href' => $href,
                        'active' => $isActive,
                        'new_tab' => (bool) $item->open_in_new_tab,
                        'children' => $childrenArray,
                    ];
                })->filter(function ($link) {
                    return $link['label'] !== '' && $link['href'] !== '#';
                });
            };

            $menuLinks = $buildMenu($menuItems)->values()->all();
        }

        if ($menuLinks !== []) {
            $navLinks = $menuLinks;
        }

        $defaultSocialLinks = [
            ['label' => 'LinkedIn', 'href' => 'https://linkedin.com', 'icon' => 'linkedin'],
            ['label' => 'X', 'href' => 'https://x.com', 'icon' => 'x'],
            ['label' => 'Instagram', 'href' => 'https://instagram.com', 'icon' => 'instagram'],
            ['label' => 'TikTok', 'href' => 'https://tiktok.com', 'icon' => 'tiktok'],
        ];
        $socialLinksRaw = $this->settingsService->getValue('navbar.social_links', $defaultSocialLinks);
        $socialLinksRaw = is_array($socialLinksRaw) ? $socialLinksRaw : $defaultSocialLinks;
        $socialLinks = collect($socialLinksRaw)->map(function ($link) use ($locale) {
            $label = $link['label'] ?? ($locale === 'ar' ? ($link['label_ar'] ?? '') : ($link['label_en'] ?? ''));
            if ($label === '') {
                $label = $locale === 'ar'
                    ? ($link['label_ar'] ?? $link['label_en'] ?? '')
                    : ($link['label_en'] ?? $link['label_ar'] ?? '');
            }

            return [
                'label' => $label,
                'href' => $link['url'] ?? $link['href'] ?? '#',
                'icon' => $link['icon'] ?? 'linkedin',
                'new_tab' => (bool) ($link['new_tab'] ?? true),
            ];
        })->all();

        $footerAddressLabel = $this->settingsService->getTranslatable(
            'footer.address_label',
            $locale,
            __('web.footer.address_label')
        );
        $footerAddressValue = $this->settingsService->getTranslatable(
            'footer.address_value',
            $locale,
            __('web.footer.address')
        );
        $footerHours = $this->settingsService->getTranslatable('footer.hours', $locale, __('web.footer.hours'));
        $footerContactLabel = $this->settingsService->getTranslatable(
            'footer.contact_label',
            $locale,
            __('web.footer.contact_label')
        );
        $footerEmail = $this->settingsService->getTranslatable('footer.email', $locale, __('web.footer.email'));
        $footerWhatsapp = $this->settingsService->getTranslatable(
            'footer.whatsapp',
            $locale,
            __('web.footer.whatsapp')
        );
        $footerPoliciesLabel = $this->settingsService->getTranslatable(
            'footer.policies_label',
            $locale,
            __('web.footer.policies_label')
        );
        $footerPolicyLinksRaw = $this->settingsService->getValue('footer.policy_links', []);
        $footerPolicyLinksRaw = is_array($footerPolicyLinksRaw) ? $footerPolicyLinksRaw : [];
        $footerPolicyLinks = collect($footerPolicyLinksRaw)->map(function ($link) use ($locale) {
            $label = $locale === 'ar' ? ($link['label_ar'] ?? '') : ($link['label_en'] ?? '');
            if ($label === '') {
                $label = $locale === 'ar'
                    ? ($link['label_ar'] ?? $link['label_en'] ?? '')
                    : ($link['label_en'] ?? $link['label_ar'] ?? '');
            }

            return [
                'label' => $label,
                'url' => $link['url'] ?? '#',
            ];
        })->all();
        $footerCertificationCardsRaw = $this->settingsService->getValue('footer.certification_cards', []);
        $footerCertificationCardsRaw = is_array($footerCertificationCardsRaw) ? $footerCertificationCardsRaw : [];
        $footerCertificationCards = collect($footerCertificationCardsRaw)
            ->map(function (array $card) use ($locale): array {
                $label = $locale === 'ar' ? ($card['label_ar'] ?? '') : ($card['label_en'] ?? '');
                if ($label === '') {
                    $label = $locale === 'ar'
                        ? ($card['label_ar'] ?? $card['label_en'] ?? '')
                        : ($card['label_en'] ?? $card['label_ar'] ?? '');
                }

                $logoPath = $card['logo'] ?? null;
                $logo = is_string($logoPath) && $logoPath !== '' ? S3Url::temporary($logoPath) : null;

                return [
                    'label' => $label,
                    'logo' => $logo,
                ];
            })
            ->filter(fn (array $card): bool => $card['label'] !== '')
            ->take(3)
            ->values()
            ->all();
        if ($footerCertificationCards === []) {
            $footerCertificationCards = [
                [
                    'label' => 'Commercial Registration Number (1010889655)',
                    'logo' => asset('img/logo.png'),
                ],
                [
                    'label' => 'Public Authority for Technical and Vocational Training License Number (2241119295411812)',
                    'logo' => asset('img/tvtc-removebg-preview.png'),
                ],
                [
                    'label' => 'National Center License (2000298631040991)',
                    'logo' => asset('img/nec-removebg-preview.png'),
                ],
            ];
        }

        $notificationCount = auth('web')->check()
            ? auth('web')->user()->unreadNotifications()->count()
            : 0;

        $contactPhone = $this->settingsService->contactPhone();
        $whatsappPhone = $this->settingsService->whatsappPhone();
        $defaultWhatsappMessage = __('web.floating_contact.default_message');

        $view->with([
            'siteTitle' => $siteTitle,
            'metaTitle' => $view->offsetExists('metaTitle') && $view->offsetGet('metaTitle') ? $view->offsetGet('metaTitle') : $metaTitle,
            'metaDescription' => $view->offsetExists('metaDescription') && $view->offsetGet('metaDescription') ? $view->offsetGet('metaDescription') : $metaDescription,
            'logoUrl' => $logoUrl,
            'faviconUrl' => $faviconUrl,
            'ogImageUrl' => $view->offsetExists('ogImageUrl') && $view->offsetGet('ogImageUrl') ? $view->offsetGet('ogImageUrl') : $ogImageUrl,
            'canonicalUrl' => $view->offsetExists('canonicalUrl') && $view->offsetGet('canonicalUrl') ? $view->offsetGet('canonicalUrl') : $canonicalUrl,
            'paymentsStripUrl' => $paymentsStripUrl,
            'paymentMethodLogos' => $paymentMethodLogos,
            'topbarLeft' => $topbarLeft,
            'topbarCenter' => $topbarCenter,
            'topbarRight' => $topbarRight,
            'navLinks' => $navLinks,
            'socialLinks' => $socialLinks,
            'footerAddressLabel' => $footerAddressLabel,
            'footerAddressValue' => $footerAddressValue,
            'footerHours' => $footerHours,
            'footerContactLabel' => $footerContactLabel,
            'footerEmail' => $footerEmail,
            'footerWhatsapp' => $footerWhatsapp,
            'footerPoliciesLabel' => $footerPoliciesLabel,
            'footerPolicyLinks' => $footerPolicyLinks,
            'footerCertificationCards' => $footerCertificationCards,
            'notificationCount' => $notificationCount,
            'contactPhone' => $contactPhone,
            'whatsappPhone' => $whatsappPhone,
            'whatsappMessage' => $view->offsetExists('whatsappMessage') && $view->offsetGet('whatsappMessage')
                ? $view->offsetGet('whatsappMessage')
                : $defaultWhatsappMessage,
        ]);
    }
}
