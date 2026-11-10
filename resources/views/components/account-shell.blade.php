<div class="full-bleed bg-[var(--page-muted-bg)]" x-data="{ mobileNavOpen: false }"
    :class="{ 'overflow-hidden': mobileNavOpen }">
    @php
        $showStandaloneMessagesLink = is_array($navLinks) && $navLinks !== [];
    @endphp

    

    @if ($showHero)
        @if (isset($hero) && trim((string) $hero) !== '')
            {{ $hero }}
        @else
            <x-banner-title :title="$heroTitle" />
        @endif
    @endif

    <div class="mx-auto w-full max-w-[1440px] overflow-hidden rounded-[25px] bg-[var(--page-muted-bg)]">
        <section class="px-6 pb-[54px] pt-6">
            @if ($showHeader)
                <div class="mb-6">
                    <h2 class="text-[28px] font-semibold  text-[var(--text-ink)]">{{ $displayTitle }}</h2>
                    @if ($subtitle)
                        <p class="text-sm text-[var(--text-subtle)]">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @if ($showNav)
                <div class="mb-4 flex items-center md:hidden">
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-[var(--nav-brand-dark)] px-4 py-2 text-sm font-semibold text-white"
                        @click="mobileNavOpen = true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
                            <path d="M3 6H21M3 12H21M3 18H21" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                        <span>{{ __('web.account.menu') }}</span>
                    </button>
                </div>
            @endif

            <div @class([
                'grid gap-4 sm:grid-cols-1',
                'md:grid-cols-[minmax(0,1fr)_360px]' => ($showSidebarChat ?? false) && $showNav,
                'md:grid-cols-[var(--account-nav-width)_1fr]' => ! (($showSidebarChat ?? false) && $showNav),
            ])>
                @if (($showSidebarChat ?? false) && $showNav)
                    <div class="order-2 min-w-0 md:order-1">
                        {{ $slot }}
                    </div>

                    <aside class="order-1 sticky top-4 hidden max-h-[calc(100vh-2rem)] flex-col gap-4 overflow-y-auto md:flex">
                        <livewire:chat.course-sidebar :course-group-id="$chatCourseGroupId" />

                        <div class="flex flex-col items-center gap-4 rounded-[20px] border border-[var(--border-subtle)] bg-white py-6">
                            <div class="flex w-full items-center gap-3 px-4">
                                <div class="h-12 w-12 overflow-hidden rounded-full bg-[var(--nav-brand-dark)]">
                                    @if ($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ __('web.profile.avatar_alt') }}"
                                            class="h-full w-full object-cover">
                                    @else
                                        <div
                                            class="flex h-full w-full items-center justify-center text-base font-semibold text-white">
                                            {{ $userInitial }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-[var(--accent-500)]">
                                        {{ $userName ?? __('web.account.guest') }}</p>
                                    <p class="text-xs text-[var(--text-subtle)]">#{{ $userId ?? '0000' }}</p>
                                </div>
                            </div>

                            <div class="w-full px-3">
                                @if ($showStandaloneMessagesLink)
                                    <a
                                        href="{{ route('dashboard.messages') }}"
                                        @class([
                                            'mb-3 flex items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold transition',
                                            'bg-[var(--nav-brand-dark)] text-white hover:opacity-95' => $active !== 'messages',
                                            'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $active === 'messages',
                                        ])>
                                        <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                                            <path d="M12 2C8.7 2 6 4.69 6 8v3.4c0 .7-.3 1.69-.7 2.29l-1.2 2A1 1 0 0 0 5 17h14a1 1 0 0 0 .9-1.45l-1.2-2c-.4-.6-.7-1.59-.7-2.29V8c0-3.31-2.7-6-6-6Z"></path>
                                            <path opacity="0.4" d="M9.5 18a2.5 2.5 0 0 0 5 0"></path>
                                        </svg>
                                        <span class="flex-1">{{ __('web.chat.title') }}</span>
                                    </a>
                                @endif
                                <x-dashboard-nav :active="$active" :custom-links="$navLinks" />
                            </div>

                            <div class="h-px w-full bg-[var(--divider-soft)]"></div>

                            <form method="POST" action="{{ route('logout') }}" class="w-full px-3">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold text-[var(--danger-strong)] transition hover:bg-[var(--danger-soft)]">
                                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                        <path
                                            d="M15.2405 22.27H15.1105C10.6705 22.27 8.5305 20.52 8.1605 16.6C8.1205 16.19 8.4205 15.82 8.8405 15.78C9.2505 15.74 9.6205 16.05 9.6605 16.46C9.9505 19.6 11.4305 20.77 15.1205 20.77H15.2505C19.3205 20.77 20.7605 19.33 20.7605 15.26V8.73998C20.7605 4.66998 19.3205 3.22998 15.2505 3.22998H15.1205C11.4105 3.22998 9.9305 4.41998 9.6605 7.61998C9.6105 8.02998 9.2705 8.33998 8.8405 8.29998C8.4205 8.26998 8.1205 7.89998 8.1505 7.48998C8.4905 3.50998 10.6405 1.72998 15.1105 1.72998H15.2405C20.1505 1.72998 22.2505 3.82998 22.2505 8.73998V15.26C22.2505 20.17 20.1505 22.27 15.2405 22.27Z">
                                        </path>
                                        <path
                                            d="M14.88 12.75H2C1.59 12.75 1.25 12.41 1.25 12C1.25 11.59 1.59 11.25 2 11.25H14.88C15.29 11.25 15.63 11.59 15.63 12C15.63 12.41 15.3 12.75 14.88 12.75Z">
                                        </path>
                                        <path
                                            d="M12.6498 16.1C12.4598 16.1 12.2698 16.03 12.1198 15.88C11.8298 15.59 11.8298 15.11 12.1198 14.82L14.9398 12L12.1198 9.18003C11.8298 8.89003 11.8298 8.41003 12.1198 8.12003C12.4098 7.83003 12.8898 7.83003 13.1798 8.12003L16.5298 11.47C16.8198 11.76 16.8198 12.24 16.5298 12.53L13.1798 15.88C13.0298 16.03 12.8398 16.1 12.6498 16.1Z">
                                        </path>
                                    </svg>
                                    <span>{{ __('web.nav.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </aside>
                @elseif ($showNav)
                    <aside
                        class="sticky top-4 hidden max-h-[calc(100vh-2rem)] flex-col items-center gap-4 overflow-y-auto rounded-[20px] border border-[var(--border-subtle)] bg-white py-6 md:flex">
                        <div class="flex w-full items-center gap-3 px-4">
                            <div class="h-12 w-12 overflow-hidden rounded-full bg-[var(--nav-brand-dark)]">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ __('web.profile.avatar_alt') }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <div
                                        class="flex h-full w-full items-center justify-center text-base font-semibold text-white">
                                        {{ $userInitial }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-[var(--accent-500)]">
                                    {{ $userName ?? __('web.account.guest') }}</p>
                                <p class="text-xs text-[var(--text-subtle)]">#{{ $userId ?? '0000' }}</p>
                            </div>
                        </div>

                        <div class="w-full px-3">
                            @if ($showStandaloneMessagesLink)
                                <a
                                    href="{{ route('dashboard.messages') }}"
                                    @class([
                                        'mb-3 flex items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold transition',
                                        'bg-[var(--nav-brand-dark)] text-white hover:opacity-95' => $active !== 'messages',
                                        'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $active === 'messages',
                                    ])>
                                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2C8.7 2 6 4.69 6 8v3.4c0 .7-.3 1.69-.7 2.29l-1.2 2A1 1 0 0 0 5 17h14a1 1 0 0 0 .9-1.45l-1.2-2c-.4-.6-.7-1.59-.7-2.29V8c0-3.31-2.7-6-6-6Z"></path>
                                        <path opacity="0.4" d="M9.5 18a2.5 2.5 0 0 0 5 0"></path>
                                    </svg>
                                    <span class="flex-1">{{ __('web.chat.title') }}</span>
                                </a>
                            @endif
                            <x-dashboard-nav :active="$active" :custom-links="$navLinks" />
                        </div>

                        <div class="h-px w-full bg-[var(--divider-soft)]"></div>

                        <form method="POST" action="{{ route('logout') }}" class="w-full px-3">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold text-[var(--danger-strong)] transition hover:bg-[var(--danger-soft)]">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M15.2405 22.27H15.1105C10.6705 22.27 8.5305 20.52 8.1605 16.6C8.1205 16.19 8.4205 15.82 8.8405 15.78C9.2505 15.74 9.6205 16.05 9.6605 16.46C9.9505 19.6 11.4305 20.77 15.1205 20.77H15.2505C19.3205 20.77 20.7605 19.33 20.7605 15.26V8.73998C20.7605 4.66998 19.3205 3.22998 15.2505 3.22998H15.1205C11.4105 3.22998 9.9305 4.41998 9.6605 7.61998C9.6105 8.02998 9.2705 8.33998 8.8405 8.29998C8.4205 8.26998 8.1205 7.89998 8.1505 7.48998C8.4905 3.50998 10.6405 1.72998 15.1105 1.72998H15.2405C20.1505 1.72998 22.2505 3.82998 22.2505 8.73998V15.26C22.2505 20.17 20.1505 22.27 15.2405 22.27Z">
                                    </path>
                                    <path
                                        d="M14.88 12.75H2C1.59 12.75 1.25 12.41 1.25 12C1.25 11.59 1.59 11.25 2 11.25H14.88C15.29 11.25 15.63 11.59 15.63 12C15.63 12.41 15.3 12.75 14.88 12.75Z">
                                    </path>
                                    <path
                                        d="M12.6498 16.1C12.4598 16.1 12.2698 16.03 12.1198 15.88C11.8298 15.59 11.8298 15.11 12.1198 14.82L14.9398 12L12.1198 9.18003C11.8298 8.89003 11.8298 8.41003 12.1198 8.12003C12.4098 7.83003 12.8898 7.83003 13.1798 8.12003L16.5298 11.47C16.8198 11.76 16.8198 12.24 16.5298 12.53L13.1798 15.88C13.0298 16.03 12.8398 16.1 12.6498 16.1Z">
                                    </path>
                                </svg>
                                <span>{{ __('web.nav.logout') }}</span>
                            </button>
                        </form>
                    </aside>
                @endif

                @if (! (($showSidebarChat ?? false) && $showNav))
                    <div class="min-w-0">
                        {{ $slot }}
                    </div>
                @endif
            </div>

            @if ($showNav)
                <div class="fixed inset-0 z-[70] md:hidden" x-show="mobileNavOpen" x-cloak>
                    <div class="absolute inset-0 bg-black/40" @click="mobileNavOpen = false"></div>

                    <aside class="absolute start-0 top-1/2 flex max-h-[70vh] w-[88vw] max-w-[300px] -translate-y-1/2 flex-col rounded-e-3xl border border-[var(--border-subtle)] bg-white py-6 shadow-xl"
                        x-show="mobileNavOpen" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="-translate-x-full">
                        <div class="mb-2 flex items-center justify-between px-4">
                            <p class="text-sm font-semibold text-[var(--text-ink)]">{{ __('web.account.menu') }}</p>
                            <button type="button" class="rounded-md p-1 text-[var(--text-subtle)]"
                                @click="mobileNavOpen = false" aria-label="{{ __('web.common.close') }}">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" aria-hidden="true">
                                    <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex w-full items-center gap-3 px-4">
                            <div class="h-12 w-12 overflow-hidden rounded-full bg-[var(--nav-brand-dark)]">
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ __('web.profile.avatar_alt') }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <div
                                        class="flex h-full w-full items-center justify-center text-base font-semibold text-white">
                                        {{ $userInitial }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-[var(--accent-500)]">
                                    {{ $userName ?? __('web.account.guest') }}</p>
                                <p class="text-xs text-[var(--text-subtle)]">#{{ $userId ?? '0000' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 min-h-0 flex-1 overflow-y-auto px-3">
                            @if ($showStandaloneMessagesLink)
                                <a
                                    href="{{ route('dashboard.messages') }}"
                                    @class([
                                        'mb-3 flex items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold transition',
                                        'bg-[var(--nav-brand-dark)] text-white hover:opacity-95' => $active !== 'messages',
                                        'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $active === 'messages',
                                    ])>
                                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2C8.7 2 6 4.69 6 8v3.4c0 .7-.3 1.69-.7 2.29l-1.2 2A1 1 0 0 0 5 17h14a1 1 0 0 0 .9-1.45l-1.2-2c-.4-.6-.7-1.59-.7-2.29V8c0-3.31-2.7-6-6-6Z"></path>
                                        <path opacity="0.4" d="M9.5 18a2.5 2.5 0 0 0 5 0"></path>
                                    </svg>
                                    <span class="flex-1">{{ __('web.chat.title') }}</span>
                                </a>
                            @endif
                            <x-dashboard-nav :active="$active" :custom-links="$navLinks" />
                        </div>

                        <div class="mt-4 h-px w-full bg-[var(--divider-soft)]"></div>

                        <form method="POST" action="{{ route('logout') }}" class="mt-2 w-full px-3">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold text-[var(--danger-strong)] transition hover:bg-[var(--danger-soft)]">
                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M15.2405 22.27H15.1105C10.6705 22.27 8.5305 20.52 8.1605 16.6C8.1205 16.19 8.4205 15.82 8.8405 15.78C9.2505 15.74 9.6205 16.05 9.6605 16.46C9.9505 19.6 11.4305 20.77 15.1205 20.77H15.2505C19.3205 20.77 20.7605 19.33 20.7605 15.26V8.73998C20.7605 4.66998 19.3205 3.22998 15.2505 3.22998H15.1205C11.4105 3.22998 9.9305 4.41998 9.6605 7.61998C9.6105 8.02998 9.2705 8.33998 8.8405 8.29998C8.4205 8.26998 8.1205 7.89998 8.1505 7.48998C8.4905 3.50998 10.6405 1.72998 15.1105 1.72998H15.2405C20.1505 1.72998 22.2505 3.82998 22.2505 8.73998V15.26C22.2505 20.17 20.1505 22.27 15.2405 22.27Z">
                                    </path>
                                    <path
                                        d="M14.88 12.75H2C1.59 12.75 1.25 12.41 1.25 12C1.25 11.59 1.59 11.25 2 11.25H14.88C15.29 11.25 15.63 11.59 15.63 12C15.63 12.41 15.3 12.75 14.88 12.75Z">
                                    </path>
                                    <path
                                        d="M12.6498 16.1C12.4598 16.1 12.2698 16.03 12.1198 15.88C11.8298 15.59 11.8298 15.11 12.1198 14.82L14.9398 12L12.1198 9.18003C11.8298 8.89003 11.8298 8.41003 12.1198 8.12003C12.4098 7.83003 12.8898 7.83003 13.1798 8.12003L16.5298 11.47C16.8198 11.76 16.8198 12.24 16.5298 12.53L13.1798 15.88C13.0298 16.03 12.8398 16.1 12.6498 16.1Z">
                                    </path>
                                </svg>
                                <span>{{ __('web.nav.logout') }}</span>
                            </button>
                        </form>
                    </aside>
                </div>
            @endif
        </section>
    </div>
</div>
