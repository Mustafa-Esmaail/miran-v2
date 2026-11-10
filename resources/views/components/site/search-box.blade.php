@props(['variant' => 'desktop'])

@php
    $isMobile = $variant === 'mobile';
@endphp

<div
    @if ($isMobile) class="px-4 py-3 sm:px-6" @else class="hidden lg:flex" @endif
    x-data="{
        query: '{{ request('search') }}',
        results: [],
        open: false,
        loading: false,
        debounceTimer: null,
        suggestUrl: '{{ route('search.suggest') }}',
        searchUrl: '{{ route('courses.search') }}',
        onInput() {
            clearTimeout(this.debounceTimer);
            if (this.query.length < 3) { this.results = []; this.open = false; return; }
            this.debounceTimer = setTimeout(() => this.fetchResults(), 300);
        },
        async fetchResults() {
            this.loading = true;
            try {
                const res = await fetch(this.suggestUrl + '?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            } finally {
                this.loading = false;
            }
        },
        submit() {
            if (!this.query.trim()) return;
            @if ($isMobile) mobileSearchOpen = false; @endif
            window.location.href = this.searchUrl + '?search=' + encodeURIComponent(this.query);
        },
        select() {
            this.open = false;
            @if ($isMobile) mobileSearchOpen = false; @endif
        }
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
>
    <div class="relative {{ $isMobile ? 'w-full' : '' }}">
        <div
            @if ($isMobile)
                class="flex items-center gap-2 rounded-full border border-(--nav-border) bg-white px-4 py-2.5"
            @else
                class="flex h-11 w-70 items-center gap-2 rounded-full border border-(--nav-border) bg-white px-4"
            @endif
        >
            <x-icon.search class="h-5 w-5 shrink-0 text-(--nav-icon-muted)" />
            <input type="text" x-model="query" @input="onInput" @keydown.enter.prevent="submit"
                @focus="query.length >= 3 && results.length && (open = true)"
                placeholder="{{ __('web.courses.search_placeholder') }}" autocomplete="off"
                class="w-full border-0 bg-transparent p-0 text-sm text-(--nav-link) placeholder:text-(--nav-icon-muted) focus:outline-none focus:ring-0">
        </div>

        {{-- Dropdown --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1" x-cloak
            class="absolute start-0 top-full z-50 mt-2 w-full min-w-[320px] rounded-2xl border border-(--nav-menu-border) bg-(--nav-menu-bg) p-2 shadow-[0_20px_60px_rgba(15,23,42,0.12)]">
            <template x-for="item in results" :key="item.url">
                @if ($isMobile)
                    <a :href="item.url" @click="select()"
                        class="flex flex-col gap-0.5 rounded-xl px-3.5 py-2.5 transition hover:bg-(--nav-hover-bg)">
                        <span class="text-sm font-semibold text-(--nav-link)" x-text="item.title"></span>
                        <span x-show="item.category" class="text-xs text-(--nav-icon-muted)" x-text="item.category"></span>
                    </a>
                @else
                    <a :href="item.url" @click="select()"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 transition hover:bg-(--nav-hover-bg)">
                        <img x-show="item.image" :src="item.image" :alt="item.title"
                            class="h-10 w-14 shrink-0 rounded-lg object-cover">
                        <div x-show="!item.image" class="h-10 w-14 shrink-0 rounded-lg bg-(--nav-hover-bg)"></div>
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="truncate text-sm font-semibold text-(--nav-link)" x-text="item.title"></span>
                            <span x-show="item.category" class="text-xs text-(--nav-icon-muted)" x-text="item.category"></span>
                        </div>
                    </a>
                @endif
            </template>
            <a :href="searchUrl + '?search=' + encodeURIComponent(query)" @click="select()"
                class="mt-1 flex items-center justify-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold text-(--nav-active) transition hover:bg-(--nav-hover-bg)">
                <x-icon.search class="h-3.5 w-3.5" />
                <span>{{ __('web.courses.search_placeholder') }}&hellip;</span>
            </a>
        </div>
    </div>
</div>
