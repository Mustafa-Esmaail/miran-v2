<header class="sticky top-0 z-50 border-b border-[var(--nav-border)] bg-[var(--nav-bg)]" x-data="{ mobileOpen: false, mobileSearchOpen: false }">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div @class([
            'flex w-full items-center justify-between gap-6 py-2  lg:py-0',
            'flex-row-reverse' => !$isRtl,
        ])>
            <a href="{{ route('home') }}"
                class="inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2">
                <x-logo.maran />
            </a>

            <nav class="hidden flex-1 items-center justify-center lg:flex" aria-label="Primary">
                <ul class="flex items-center gap-3">
                    @foreach ($navLinks as $link)
                        <li class="{{ $link['is_courses'] ?? false ? 'relative group' : '' }}">
                            <a href="{{ $link['href'] ?? '#' }}" @class([
                                'inline-flex items-center gap-1 rounded-[200px] px-3 py-2 text-base font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2',
                                'bg-[var(--nav-active)] text-[var(--nav-active-text)] shadow-[0_4px_30px_rgb(var(--nav-menu-shadow)_/_0.05)]' =>
                                    $link['active'] ?? false,
                                'text-[var(--nav-link)] hover:text-[var(--nav-link-hover)] hover:bg-[var(--nav-hover-bg)] active:translate-y-px' => !(
                                    $link['active'] ?? false
                                ),
                            ])
                                @if (!empty($link['new_tab'])) target="_blank" rel="noopener noreferrer" @endif>
                                <span>{{ $link['label'] ?? '' }}</span>
                                @if ($link['is_courses'] ?? false)
                                    <x-icon.chevron-down class="h-4 w-4 text-current" />
                                @endif
                            </a>

                            @if (($link['is_courses'] ?? false) && $courseCategories !== [])
                                <div
                                    class="pointer-events-none absolute start-0 top-full z-20 w-72 pt-2 opacity-0 transition duration-150 group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                    <div
                                        class="rounded-2xl border border-[var(--nav-menu-border)] bg-[var(--nav-menu-bg)] p-3 shadow-[0_20px_60px_rgba(15,23,42,0.12)]">
                                        <div class="flex flex-col gap-1">
                                            @foreach ($courseCategories as $category)
                                                <a href="{{ $category['href'] }}"
                                                    class="flex items-center justify-between rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-icon-muted)] transition hover:bg-[var(--nav-hover-bg)]">
                                                    <span>{{ $category['name'] }}</span>
                                                    <x-icon.chevron-down class="h-4 w-4 rotate-[-90deg] text-current" />
                                                </a>
                                            @endforeach
                                            <a href="{{ route('bundles.index') }}"
                                                class="flex items-center justify-between rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-icon-muted)] transition hover:bg-[var(--nav-hover-bg)]">
                                                <span>{{ __('web.nav.bundles') }}</span>
                                                <x-icon.chevron-down class="h-4 w-4 rotate-[-90deg] text-current" />
                                            </a>
                                        </div>
                                        <div class="mt-2 h-px w-full bg-[var(--nav-menu-border)]"></div>
                                        <a href="{{ route('courses.index') }}"
                                            class="mt-2 inline-flex w-full items-center justify-center rounded-[100px] bg-[var(--nav-active)] px-3.5 py-3 text-sm font-semibold text-[var(--nav-active-text)]">
                                            {{ __('web.home.view_courses') }}
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-3 lg:gap-5">
                {{-- Desktop autocomplete search --}}
                <x-site.search-box variant="desktop" />
                <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full text-[var(--nav-icon)] transition-colors hover:text-[var(--nav-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 lg:hidden"
                    aria-label="{{ __('web.nav.search') }}" @click="mobileSearchOpen = !mobileSearchOpen">
                    <x-icon.search class="h-6 w-6" />
                </button>
                @if (auth('web')->check())
<a href="{{ route('dashboard.wishlist') }}"
                    class="hidden h-10 w-10 items-center justify-center rounded-full text-[var(--nav-icon)] transition-colors hover:text-[var(--nav-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 active:translate-y-px lg:inline-flex"
                    aria-label="{{ __('web.nav.wishlist') }}">
                    <x-icon.heart class="h-6 w-6" />
                </a>
@endif
                @if (auth('web')->check())
                    <a href="{{ route('dashboard.notifications') }}"
                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-[var(--nav-icon)] transition-colors hover:text-[var(--nav-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 active:translate-y-px"
                        aria-label="{{ __('web.notifications.title') }}">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm6-6V11a6 6 0 1 0-12 0v5l-1.75 1.75a1 1 0 0 0 .7 1.7h14.1a1 1 0 0 0 .7-1.7L18 16Z"
                                fill="currentColor" />
                        </svg>
                        @if (($notificationCount ?? 0) > 0)
<span
                                class="absolute -top-1 -end-1 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[var(--nav-active)] px-1 text-[10px] font-semibold text-[var(--nav-active-text)]">
                                {{ $notificationCount }}
                            </span>
@endif
                    </a>
                @endif
                <a href="{{ route('cart') }}"
                    class="relative inline-flex h-10  w-10 items-center justify-center rounded-full text-[var(--nav-icon)] transition-colors hover:text-[var(--nav-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 active:translate-y-px {{ $cartCount > 0 ? 'is-active' : '' }}"
                    aria-label="{{ __('web.nav.cart') }}">
                    <x-icon.bag class="h-6 w-6" />
                    @if ($cartCount > 0)
<span
                            class="absolute -top-1 -end-1 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[var(--nav-active)] px-1 text-[10px] font-semibold text-[var(--nav-active-text)]">
                            {{ $cartCount }}
                        </span>
@endif
                </a>

                <span class="hidden h-10 w-px bg-[var(--nav-divider)] opacity-50 lg:block" aria-hidden="true"></span>

                @if (auth('web')->check())
                    <div class="group relative hidden lg:flex">
                        <button type="button"
                            class="flex items-center gap-3 rounded-xl border border-transparent px-1.5 py-1 transition group-hover:border-[var(--nav-active)] group-hover:shadow-[0_4px_30px_rgb(var(--nav-menu-shadow)_/_0.05)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2"
                            aria-haspopup="true">
                            <div
                                class="h-10 w-10 overflow-hidden rounded-full bg-[var(--nav-active)] text-sm font-semibold text-[var(--nav-active-text)]">
                                @if (auth('web')->user()?->avatar_path)
<img src="{{ \App\Support\S3Url::temporary(auth('web')->user()->avatar_path) }}"
                                        alt="{{ __('web.profile.avatar_alt') }}" class="h-full w-full object-cover">
@else
<div class="flex h-full w-full items-center justify-center">
                                        {{ \Illuminate\Support\Str::upper(mb_substr(auth('web')->user()->name ?? 'U', 0, 1)) }}
                                    </div>
@endif
                            </div>
                            <x-icon.chevron-down class="h-[22px] w-[22px] text-[var(--nav-icon-muted)]" />
                        </button>

                        <div
                            class="pointer-events-none absolute end-0 top-full z-20 mt-2 w-64 translate-y-2 opacity-0 transition duration-150 group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0 group-focus-within:opacity-100">
                            <div
                                class="flex flex-col gap-1 rounded-2xl border border-[var(--nav-menu-border)] bg-[var(--nav-menu-bg)] p-3 shadow-[0_4px_30px_rgb(var(--nav-menu-shadow)_/_0.05)]">
                                <a href="{{ route('dashboard') }}"
                                    class="flex items-center gap-2 rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-icon-muted)] transition hover:bg-[var(--nav-hover-bg)]">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[var(--nav-icon-muted)]"
                                        preserveAspectRatio="none">
                                        <path
                                            d="M7.24 2H5.34C3.15 2 2 3.15 2 5.33V7.23C2 9.41 3.15 10.56 5.33 10.56H7.23C9.41 10.56 10.56 9.41 10.56 7.23V5.33C10.57 3.15 9.42 2 7.24 2Z"
                                            fill="currentColor" />
                                        <path opacity="0.4"
                                            d="M18.6699 2H16.7699C14.5899 2 13.4399 3.15 13.4399 5.33V7.23C13.4399 9.41 14.5899 10.56 16.7699 10.56H18.6699C20.8499 10.56 21.9999 9.41 21.9999 7.23V5.33C21.9999 3.15 20.8499 2 18.6699 2Z"
                                            fill="currentColor" />
                                        <path
                                            d="M18.6699 13.4297H16.7699C14.5899 13.4297 13.4399 14.5797 13.4399 16.7597V18.6597C13.4399 20.8397 14.5899 21.9897 16.7699 21.9897H18.6699C20.8499 21.9897 21.9999 20.8397 21.9999 18.6597V16.7597C21.9999 14.5797 20.8499 13.4297 18.6699 13.4297Z"
                                            fill="currentColor" />
                                        <path opacity="0.4"
                                            d="M7.24 13.4297H5.34C3.15 13.4297 2 14.5797 2 16.7597V18.6597C2 20.8497 3.15 21.9997 5.33 21.9997H7.23C9.41 21.9997 10.56 20.8497 10.56 18.6697V16.7697C10.57 14.5797 9.42 13.4297 7.24 13.4297Z"
                                            fill="currentColor" />
                                    </svg>
                                    <span class="flex-grow text-left">{{ __('web.nav.dashboard') }}</span>
                                </a>
                                <a href="{{ route('dashboard.profile') }}"
                                    class="flex items-center gap-2 rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-icon-muted)] transition hover:bg-[var(--nav-hover-bg)]">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--nav-icon-muted)]"
                                        preserveAspectRatio="none">
                                        <path opacity="0.5"
                                            d="M17.3736 7.26121C18.6533 5.98145 18.6533 3.90656 17.3736 2.62681C16.0938 1.34705 14.019 1.34705 12.7392 2.62681L12 3.36601C12.0101 3.39657 12.0206 3.42756 12.0315 3.45895C12.3025 4.23991 12.8137 5.26366 13.7753 6.22534C14.737 7.18702 15.7608 7.69823 16.5417 7.96917C16.573 7.98001 16.6038 7.99047 16.6343 8.00054L17.3736 7.26121Z"
                                            fill="currentColor" />
                                        <path
                                            d="M12.0322 3.33398L12.0004 3.36581C12.0105 3.39638 12.021 3.42737 12.0319 3.45876C12.3029 4.23971 12.8141 5.26347 13.7757 6.22515C14.7374 7.18683 15.7612 7.69804 16.5421 7.96898C16.5731 7.97973 16.6037 7.99009 16.6339 8.00009L9.50016 15.1338C9.01923 15.6147 8.7787 15.8553 8.51355 16.0621C8.20076 16.306 7.86232 16.5152 7.50423 16.6859C7.20066 16.8305 6.87801 16.9381 6.23273 17.1532L2.82995 18.2874C2.5124 18.3933 2.1623 18.3106 1.92561 18.074C1.68892 17.8373 1.60627 17.4872 1.71212 17.1696L2.84638 13.7668C3.06148 13.1215 3.16903 12.7989 3.3137 12.4953C3.48436 12.1372 3.69353 11.7988 3.9375 11.486C4.1443 11.2209 4.38479 10.9804 4.8657 10.4995L12.0322 3.33398Z"
                                            fill="currentColor" />
                                    </svg>
                                    <span class="flex-grow text-left">{{ __('web.profile.edit_profile') }}</span>
                                </a>
                                <a href="{{ route('password.request') }}"
                                    class="flex items-center gap-2 rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-icon-muted)] transition hover:bg-[var(--nav-hover-bg)]">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--nav-icon-muted)]"
                                        preserveAspectRatio="none">
                                        <path opacity="0.5"
                                            d="M1.66675 13.333C1.66675 10.976 1.66675 9.79747 2.39898 9.06524C3.13121 8.33301 4.30973 8.33301 6.66675 8.33301H13.3334C15.6904 8.33301 16.8689 8.33301 17.6012 9.06524C18.3334 9.79747 18.3334 10.976 18.3334 13.333C18.3334 15.69 18.3334 16.8685 17.6012 17.6008C16.8689 18.333 15.6904 18.333 13.3334 18.333H6.66675C4.30973 18.333 3.13121 18.333 2.39898 17.6008C1.66675 16.8685 1.66675 15.69 1.66675 13.333Z"
                                            fill="currentColor" />
                                        <path
                                            d="M5.625 6.66699C5.625 4.25075 7.58375 2.29199 10 2.29199C12.4162 2.29199 14.375 4.25075 14.375 6.66699V8.33664C14.8478 8.34105 15.2612 8.352 15.625 8.37917V6.66699C15.625 3.56039 13.1066 1.04199 10 1.04199C6.8934 1.04199 4.375 3.56039 4.375 6.66699V8.37917C4.73876 8.352 5.1522 8.34105 5.625 8.33664V6.66699Z"
                                            fill="currentColor" />
                                    </svg>
                                    <span class="flex-grow text-left">{{ __('web.profile.change_password') }}</span>
                                </a>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-2 rounded-[100px] px-3.5 py-3 text-sm font-semibold text-[var(--nav-menu-danger)] transition hover:bg-[var(--nav-hover-bg)]">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-[var(--nav-menu-danger)]" preserveAspectRatio="none">
                                            <path opacity="0.5"
                                                d="M13.3332 1.66634L12.4998 1.66634C10.1428 1.66634 8.96466 1.66653 8.23243 2.39876C7.50019 3.131 7.50019 4.30951 7.50019 6.66653L7.50019 13.3332C7.5002 15.6902 7.50019 16.8687 8.23243 17.601C8.96447 18.333 10.1422 18.333 12.498 18.333L12.4998 18.333L13.3332 18.333C15.6902 18.333 16.8687 18.333 17.6009 17.6008C18.3331 16.8685 18.3331 15.69 18.3331 13.333L18.3331 6.66634L18.3331 6.66632C18.3331 4.30931 18.3331 3.1308 17.6009 2.39857C16.8687 1.66634 15.6902 1.66634 13.3332 1.66634Z"
                                                fill="currentColor" />
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M13.125 9.99999C13.125 9.65481 12.8452 9.37499 12.5 9.37499L3.3562 9.37499L4.99008 7.97452C5.25216 7.74989 5.28251 7.35532 5.05787 7.09324C4.83323 6.83117 4.43867 6.80081 4.17659 7.02545L1.25992 9.52545C1.12139 9.64419 1.04167 9.81754 1.04167 9.99999C1.04167 10.1824 1.12139 10.3558 1.25992 10.4745L4.17659 12.9745C4.43867 13.1992 4.83323 13.1688 5.05787 12.9067C5.28251 12.6447 5.25216 12.2501 4.99008 12.0255L3.3562 10.625L12.5 10.625C12.8452 10.625 13.125 10.3452 13.125 9.99999Z"
                                                fill="currentColor" />
                                        </svg>
                                        <span class="flex-grow text-left">{{ __('web.nav.logout') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
@else
<a href="{{ route('login') }}"
                        class="hidden h-12 items-center justify-center rounded-full border-2 border-[var(--nav-active)] px-6 text-sm font-bold text-[var(--nav-active)] transition hover:-translate-y-px hover:bg-[var(--nav-active)] hover:text-[var(--nav-active-text)] hover:shadow-[0_4px_30px_rgb(var(--nav-menu-shadow)_/_0.05)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 active:translate-y-px lg:inline-flex">
                        {{ __('web.nav.login') }}
                    </a>
                @endif
                <a href="{{ auth('web')->check() ? route('dashboard') : route('login') }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full text-[var(--nav-icon)] transition-colors hover:text-[var(--nav-link-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 lg:hidden"
                    aria-label="{{ __('web.nav.login') }}">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" aria-hidden="true">
                        <path
                            d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.418 0-8 2.015-8 4.5 0 .828.672 1.5 1.5 1.5h13c.828 0 1.5-.672 1.5-1.5 0-2.485-3.582-4.5-8-4.5Z"
                            fill="currentColor" />
                    </svg>
                </a>

                <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[var(--nav-border)] text-[var(--nav-icon)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent focus-visible:ring-offset-2 lg:hidden"
                    aria-label="{{ __('web.nav.open_menu') }}" :aria-expanded="mobileOpen ? 'true' : 'false'"
                                @click="mobileOpen = !mobileOpen">
                                <x-icon.menu class="h-5 w-5" />
                                </button>
                        </div>
                    </div>
                </div>

                <div class="border-t border-[var(--nav-border)] bg-[var(--nav-bg)] lg:hidden" x-cloak
                    x-show="mobileSearchOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2">
                    {{-- Mobile autocomplete search --}}
                    <x-site.search-box variant="mobile" />
                </div>

                <div class="border-t border-[var(--nav-border)] bg-[var(--nav-bg)] lg:hidden" x-cloak
                    x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2">
                    <nav class="w-full px-4 py-5 sm:px-6 lg:px-8" aria-label="Mobile">
                        <ul class="flex flex-col gap-2">
                            @foreach ($navLinks as $link)
                                <li>
                                    <a href="{{ $link['href'] ?? '#' }}" @class([
                                        'flex items-center justify-between rounded-2xl px-4 py-3 text-base font-semibold transition',
                                        'bg-[var(--nav-active)] text-[var(--nav-active-text)]' =>
                                            $link['active'] ?? false,
                                        'text-[var(--nav-link)] hover:bg-[var(--nav-hover-bg)]' => !(
                                            $link['active'] ?? false
                                        ),
                                    ])
                                        @if (!empty($link['new_tab'])) target="_blank" rel="noopener noreferrer" @endif
                                        @click="mobileOpen = false">
                                        <span>{{ $link['label'] ?? '' }}</span>
                                        @if ($link['is_courses'] ?? false)
                                            <x-icon.chevron-down class="h-4 w-4 text-current" />
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4 flex items-center gap-3">
                            @if (auth('web')->check())
                                <a href="{{ route('dashboard') }}"
                                    class="flex-1 rounded-[100px] border border-[var(--nav-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--nav-link)]"
                                    @click="mobileOpen = false">
                                    {{ __('web.nav.dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="flex-1 rounded-[100px] border border-[var(--nav-border)] px-4 py-3 text-center text-sm font-semibold text-[var(--nav-link)]"
                                    @click="mobileOpen = false">
                                    {{ __('web.nav.login') }}
                                </a>
                            @endif
                            <a href="{{ route('cart') }}"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[var(--nav-border)] text-[var(--nav-icon)]"
                                aria-label="{{ __('web.nav.cart') }}" @click="mobileOpen = false">
                                <x-icon.bag class="h-5 w-5" />
                            </a>
                        </div>
                    </nav>
                </div>
</header>
