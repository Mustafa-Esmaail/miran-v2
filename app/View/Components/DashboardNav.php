<?php

namespace App\View\Components;

use App\Models\MenuItem;
use App\Repositories\Contracts\MenuRepositoryInterface;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DashboardNav extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $links;

    public function __construct(public string $active = 'courses', public ?array $customLinks = null)
    {
        if (is_array($customLinks) && $customLinks !== []) {
            $this->links = $customLinks;

            return;
        }

        $activeKey = $this->resolveActiveKey($active);

        $iconMap = [
            'dashboard' => '<path d="M7.24 2H5.34C3.15 2 2 3.15 2 5.33V7.23C2 9.41 3.15 10.56 5.33 10.56H7.23C9.41 10.56 10.56 9.41 10.56 7.23V5.33C10.57 3.15 9.42 2 7.24 2Z"></path><path opacity="0.4" d="M18.6695 2H16.7695C14.5895 2 13.4395 3.15 13.4395 5.33V7.23C13.4395 9.41 14.5895 10.56 16.7695 10.56H18.6695C20.8495 10.56 21.9995 9.41 21.9995 7.23V5.33C21.9995 3.15 20.8495 2 18.6695 2Z"></path><path d="M18.6695 13.43H16.7695C14.5895 13.43 13.4395 14.58 13.4395 16.76V18.66C13.4395 20.84 14.5895 21.99 16.7695 21.99H18.6695C20.8495 21.99 21.9995 20.84 21.9995 18.66V16.76C21.9995 14.58 20.8495 13.43 18.6695 13.43Z"></path><path opacity="0.4" d="M7.24 13.43H5.34C3.15 13.43 2 14.58 2 16.76V18.66C2 20.85 3.15 22 5.33 22H7.23C9.41 22 10.56 20.85 10.56 18.67V16.77C10.57 14.58 9.42 13.43 7.24 13.43Z"></path>',
            'profile' => '<path opacity="0.4" d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"></path><path d="M12.0002 14.5C6.99015 14.5 2.91016 17.86 2.91016 22C2.91016 22.28 3.13016 22.5 3.41016 22.5H20.5901C20.8701 22.5 21.0901 22.28 21.0901 22C21.0901 17.86 17.0102 14.5 12.0002 14.5Z"></path>',
            'courses' => '<path opacity="0.4" d="M21.6602 10.44L20.6802 14.62C19.8402 18.23 18.1802 19.69 15.0602 19.39C14.5602 19.35 14.0202 19.26 13.4402 19.12L11.7602 18.72C7.59018 17.73 6.30018 15.67 7.28018 11.49L8.26018 7.30001C8.46018 6.45001 8.70018 5.71001 9.00018 5.10001C10.1702 2.68001 12.1602 2.03001 15.5002 2.82001L17.1702 3.21001C21.3602 4.19001 22.6402 6.26001 21.6602 10.44Z"></path><path d="M15.0584 19.39C14.4384 19.81 13.6584 20.16 12.7084 20.47L11.1284 20.99C7.15839 22.27 5.06839 21.2 3.77839 17.23L2.49839 13.28C1.21839 9.30998 2.27839 7.20998 6.24839 5.92998L7.82839 5.40998C8.23839 5.27998 8.62839 5.16998 8.99839 5.09998C8.69839 5.70998 8.45839 6.44998 8.25839 7.29998L7.27839 11.49C6.29839 15.67 7.58839 17.73 11.7584 18.72L13.4384 19.12C14.0184 19.26 14.5584 19.35 15.0584 19.39Z"></path><path d="M17.4894 10.51C17.4294 10.51 17.3694 10.5 17.2994 10.49L12.4494 9.26002C12.0494 9.16002 11.8094 8.75002 11.9094 8.35002C12.0094 7.95002 12.4194 7.71002 12.8194 7.81002L17.6694 9.04002C18.0694 9.14002 18.3094 9.55002 18.2094 9.95002C18.1294 10.28 17.8194 10.51 17.4894 10.51Z"></path><path d="M14.5611 13.89C14.5011 13.89 14.4411 13.88 14.3711 13.87L11.4611 13.13C11.0611 13.03 10.8211 12.62 10.9211 12.22C11.0211 11.82 11.4311 11.58 11.8311 11.68L14.7411 12.42C15.1411 12.52 15.3811 12.93 15.2811 13.33C15.2011 13.67 14.9011 13.89 14.5611 13.89Z"></path>',
            'orders' => '<path opacity="0.4" d="M21.0409 7.19L12.0009 12.42L2.96094 7.19C3.36094 6.45 3.94094 5.8 4.59094 5.44L9.93094 2.48C11.0709 1.84 12.9309 1.84 14.0709 2.48L19.4109 5.44C20.0609 5.8 20.6409 6.45 21.0409 7.19Z"></path><path opacity="0.6" d="M12.0006 12.42V22C11.2506 22 10.5006 21.84 9.93062 21.52L4.59063 18.56C3.38063 17.89 2.39062 16.21 2.39062 14.83V9.17C2.39062 8.53 2.61062 7.83 2.96062 7.19L12.0006 12.42Z"></path><path d="M21.61 9.17V14.83C21.61 16.21 20.62 17.89 19.41 18.56L14.07 21.52C13.5 21.84 12.75 22 12 22V12.42L21.04 7.19C21.39 7.83 21.61 8.53 21.61 9.17Z"></path>',
            'downloads' => '<path opacity="0.4" d="M20.5 10.19H17.61C15.24 10.19 13.31 8.26 13.31 5.89V3C13.31 2.45 12.86 2 12.31 2H8.07C4.99 2 2.5 4 2.5 7.57V16.43C2.5 20 4.99 22 8.07 22H15.93C19.01 22 21.5 20 21.5 16.43V11.19C21.5 10.64 21.05 10.19 20.5 10.19Z"></path><path d="M15.7997 2.20999C15.3897 1.79999 14.6797 2.07999 14.6797 2.64999V6.13999C14.6797 7.59999 15.9197 8.80999 17.4297 8.80999C18.3797 8.81999 19.6997 8.81999 20.8297 8.81999C21.3997 8.81999 21.6997 8.14999 21.2997 7.74999C19.8597 6.29999 17.2797 3.68999 15.7997 2.20999Z"></path><path d="M12.2814 14.72C11.9914 14.43 11.5114 14.43 11.2214 14.72L10.5014 15.44V11.25C10.5014 10.84 10.1614 10.5 9.75141 10.5C9.34141 10.5 9.00141 10.84 9.00141 11.25V15.44L8.28141 14.72C7.99141 14.43 7.51141 14.43 7.22141 14.72C6.93141 15.01 6.93141 15.49 7.22141 15.78L9.22141 17.78C9.23141 17.79 9.24141 17.79 9.24141 17.8C9.30141 17.86 9.38141 17.91 9.46141 17.95C9.56141 17.98 9.65141 18 9.75141 18C9.85141 18 9.94141 17.98 10.0314 17.94C10.1214 17.9 10.2014 17.85 10.2814 17.78L12.2814 15.78C12.5714 15.49 12.5714 15.01 12.2814 14.72Z"></path>',
            'notifications' => '<path d="M12 2C8.7 2 6 4.69 6 8v3.4c0 .7-.3 1.69-.7 2.29l-1.2 2A1 1 0 0 0 5 17h14a1 1 0 0 0 .9-1.45l-1.2-2c-.4-.6-.7-1.59-.7-2.29V8c0-3.31-2.7-6-6-6Z"></path><path opacity=\"0.4\" d=\"M9.5 18a2.5 2.5 0 0 0 5 0\"></path>',
            'wishlist' => '<path d="M12.765 4.70229L12 5.52422L11.235 4.70229C9.12233 2.43257 5.69709 2.43257 3.58447 4.70229C1.47184 6.972 1.47184 10.6519 3.58447 12.9217L10.4699 20.3191C11.315 21.227 12.685 21.227 13.5301 20.3191L20.4155 12.9217C22.5282 10.6519 22.5282 6.972 20.4155 4.70229C18.3029 2.43257 14.8777 2.43257 12.765 4.70229Z"></path>',
            'delete' => '<path d="M5.5 6.5H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M9 6.5V5.5C9 4.4 9.9 3.5 11 3.5H13C14.1 3.5 15 4.4 15 5.5V6.5" stroke="currentColor" stroke-width="1.5"></path><path d="M7.5 9V17.5C7.5 18.6 8.4 19.5 9.5 19.5H14.5C15.6 19.5 16.5 18.6 16.5 17.5V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M10.5 11.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M13.5 11.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>',
            'certificate' => '<path opacity="0.4" d="M12 16C15.3137 16 18 13.3137 18 10C18 6.68629 15.3137 4 12 4C8.68629 4 6 6.68629 6 10C6 13.3137 8.68629 16 12 16Z"></path><path d="M8.91 14.83L8 22L12 19.78L16 22L15.09 14.82C14.14 15.26 13.1 15.5 12 15.5C10.9 15.5 9.86 15.26 8.91 14.83Z"></path><path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" opacity="0.4"></path>',
            'certificate-form' => '<path d="M21 7v10c0 3-1.5 5-5 5H8c-3.5 0-5-2-5-5V7c0-3 1.5-5 5-5h8c3.5 0 5 2 5 5Z" opacity="0.4"/><path d="M14.5 2v3.5c0 1 .5 1.5 1.5 1.5h3.5M8 13h4M8 17h8" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
            'help' => '<path d="M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1Z" />',
        ];

        $locale = app()->getLocale();
        $chatUnreadCount = $this->resolveChatUnreadCount();
        $currentPath = trim(parse_url(url()->current(), PHP_URL_PATH) ?? '', '/');
        $menuItems = app(MenuRepositoryInterface::class)->getActiveItemsForLocation('user_dashboard');
        $menuLinks = $menuItems->map(function (MenuItem $item) use ($locale, $currentPath, $iconMap): array {
            $children = $item->children
                ->map(fn (MenuItem $child): array => $this->buildMenuLink($child, $locale, $currentPath, $iconMap))
                ->filter(fn (array $child): bool => $child['label'] !== '' && $child['route'] !== '#')
                ->values()
                ->all();

            $link = $this->buildMenuLink($item, $locale, $currentPath, $iconMap);
            $hasChildren = count($children) > 0;
            $hasActiveChild = collect($children)->contains(fn (array $child): bool => $child['is_active']);

            $link['children'] = $children;
            $link['has_children'] = $hasChildren;
            $link['is_active'] = $link['is_active'] || $hasActiveChild;

            return $link;
        })->filter(function (array $link): bool {
            if ($link['label'] === '') {
                return false;
            }

            return $link['route'] !== '#' || $link['has_children'];
        })->values()->all();

        $rawLinks = [
            'dashboard' => ['label' => __('web.nav.dashboard'), 'route' => route('dashboard'), 'icon' => 'dashboard'],
            'profile' => ['label' => __('web.profile.edit_profile'), 'route' => route('dashboard.profile'), 'icon' => 'profile'],
            'courses' => ['label' => __('web.my_courses.title'), 'route' => route('dashboard.courses'), 'icon' => 'courses'],
            'orders' => ['label' => __('web.orders.title'), 'route' => route('dashboard.orders'), 'icon' => 'orders'],
            'downloads' => ['label' => __('web.downloads.title'), 'route' => route('dashboard.downloads'), 'icon' => 'downloads'],
            'messages' => [
                'label' => __('web.chat.title'),
                'route' => route('dashboard.messages'),
                'icon' => 'notifications',
                'badge_count' => $chatUnreadCount,
            ],
            'notifications' => ['label' => __('web.notifications.title'), 'route' => route('dashboard.notifications'), 'icon' => 'notifications'],
            'certificates' => ['label' => __('web.certificates.title'), 'route' => route('dashboard.certificates'), 'icon' => 'certificate'],
            'wishlist' => ['label' => __('web.wishlist.title'), 'route' => route('dashboard.wishlist'), 'icon' => 'wishlist'],
        ];

        $staticLinks = collect($rawLinks)->map(function (array $link, string $key) use ($iconMap, $activeKey) {
            $icon = $iconMap[$link['icon']] ?? '';

            return [
                'key' => $key,
                'label' => $link['label'],
                'route' => $link['route'],
                'icon' => $iconMap[$link['icon']] ?? $iconMap['help'],
                'is_active' => $activeKey === $key,
                'is_delete' => $key === 'delete-account',
                'open_in_new_tab' => false,
                'badge_count' => (int) ($link['badge_count'] ?? 0),
            ];
        })->values();

        $deleteLink = [
            'key' => 'delete-account',
            'label' => __('web.nav.delete_account'),
            'route' => route('dashboard.delete-account'),
            'icon' => $iconMap['delete'] ?? '',
            'is_active' => $activeKey === 'delete-account',
            'is_delete' => true,
            'open_in_new_tab' => false,
        ];

        $this->links = $staticLinks
            ->merge($menuLinks)
            ->push($deleteLink)
            ->values()
            ->all();
    }

    private function resolveActiveKey(string $active): string
    {
        $rawKeys = [
            'dashboard',
            'profile',
            'courses',
            'orders',
            'downloads',
            'certificates',
            'messages',
            'notifications',
            'wishlist',
            'delete-account',
        ];

        if (in_array($active, $rawKeys, true)) {
            return $active;
        }

        if (request()->routeIs('dashboard')) {
            return 'dashboard';
        }

        if (request()->routeIs('dashboard.profile')) {
            return 'profile';
        }

        if (request()->routeIs('dashboard.courses*')) {
            return 'courses';
        }

        if (request()->routeIs('dashboard.orders*')) {
            return 'orders';
        }

        if (request()->routeIs('dashboard.downloads*')) {
            return 'downloads';
        }

        if (request()->routeIs('dashboard.certificates*', 'dashboard.certificate-form')) {
            return 'certificates';
        }

        if (request()->routeIs('dashboard.messages*')) {
            return 'messages';
        }

        if (request()->routeIs('dashboard.notifications')) {
            return 'notifications';
        }

        if (request()->routeIs('dashboard.wishlist*')) {
            return 'wishlist';
        }

        if (request()->routeIs('dashboard.delete-account')) {
            return 'delete-account';
        }

        return 'dashboard';
    }

    public function render(): View
    {
        return view('components.dashboard-nav');
    }

    /**
     * @param  array<string, string>  $iconMap
     * @return array<string, mixed>
     */
    private function buildMenuLink(MenuItem $item, string $locale, string $currentPath, array $iconMap): array
    {
        $label = $item->getTranslation('label', $locale)
            ?: $item->getTranslation('label', 'en')
            ?: $item->getTranslation('label', 'ar');
        $href = '#';
        if ($item->type === 'page' && $item->page && $item->page->is_active) {
            $href = route('pages.show', $item->page->slug);
        } elseif ($item->type === 'custom' && $item->url) {
            $href = $item->url;
        }

        $linkPath = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');

        return [
            'key' => 'menu-'.$item->id,
            'label' => $label,
            'route' => $href,
            'icon' => $iconMap['help'] ?? '',
            'is_active' => $linkPath !== '' && $currentPath === $linkPath,
            'is_delete' => false,
            'open_in_new_tab' => (bool) $item->open_in_new_tab,
            'has_children' => false,
            'children' => [],
            'badge_count' => 0,
        ];
    }

    private function resolveChatUnreadCount(): int
    {
        $user = auth('web')->user();

        if (! $user) {
            return 0;
        }

        return app(ChatService::class)->getUnreadCountForUser($user);
    }
}
