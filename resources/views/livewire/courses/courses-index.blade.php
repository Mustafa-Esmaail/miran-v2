<div class="bg-[#f5f5f6] text-neutral-text">
    {{-- HERO --}}
    <section class="relative overflow-hidden bg-brand-dark text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;">
        </div>
        <div class="pointer-events-none absolute -top-[420px] end-[-380px] h-[760px] w-[760px] rounded-full blur-2xl"
            style="background: radial-gradient(circle, rgba(113,150,147,0.4), rgba(113,150,147,0));"></div>

        <div class="app-container">
            <div class="flex flex-col items-center gap-8 py-16 text-center sm:py-20 lg:py-24">
                <h1 class="text-4xl font-semibold text-[#f5f5f6] sm:text-5xl">{{ $title }}</h1>

                {{-- <div class="flex w-full max-w-3xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-center">
                    <div
                        class="flex flex-1 items-center gap-3 rounded-full bg-white/95 px-6 py-3 text-[#82868e] shadow-sm">
                        <x-icon.search class="h-5 w-5 text-[#82868e]" />
                        <input type="search" wire:model.live.debounce.400ms="search"
                            placeholder="{{ __('web.courses.search_placeholder') }}"
                            class="w-full border-0 bg-transparent p-0 text-base text-[#242528] placeholder:text-[#82868e] focus:ring-0">
                    </div>

                    <div class="relative">
                        <select wire:model.live="categoryId"
                            class="appearance-none rounded-full bg-[#99745c] px-6 py-3 pe-12 text-base text-white focus:ring-0">
                            <option value="">{{ __('web.courses.dropdown_label') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-icon.chevron-down
                            class="pointer-events-none absolute end-4 top-1/2 h-5 w-5 -translate-y-1/2 text-white" />
                    </div>
                </div> --}}
            </div>
        </div>
    </section>

    {{-- CONTENT --}}
    <section class="relative">
        <div class="app-container py-10 lg:py-12">
            <div class="flex flex-col gap-6">
                {{-- TOP CONTROLS --}}
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    {{-- LEFT: Filter + Sort side-by-side --}}
                    {{-- <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button type="button" wire:click="$toggle('showFilters')"
                            class="inline-flex items-center gap-1 rounded-3xl border border-[#ced0d3] bg-white px-4 py-3 text-base font-semibold text-[#4b4c53]">
                            <x-icon.filter class="h-6 w-6 text-[#242528]" />
                            <span>{{ __('web.filters.filter') }}</span>
                            <span class="rounded-full bg-[#fe4a23] px-2 py-0.5 text-[10px] font-semibold text-white">
                                {{ $activeFilters }}
                            </span>
                        </button>

                        <div class="relative w-full sm:w-auto sm:min-w-[220px]" x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                class="flex w-full items-center justify-center gap-1 rounded-3xl border border-[#99745c] bg-white px-4 py-3 text-base text-[#4b4c53]">
                                <x-icon.menu class="h-6 w-6 text-[#242528]" />
                                <span>{{ $currentSortLabel }}</span>
                            </button>

                            <div x-cloak x-show="open" @click.away="open = false"
                                class="absolute end-0 z-10 mt-3 w-full rounded-lg border border-[#ced0d3] bg-white px-4 py-3 shadow-sm">
                                <div class="flex flex-col gap-3">
                                    @foreach ($sortOptions as $optionValue => $optionLabel)
                                        <button type="button" wire:click="$set('sort', '{{ $optionValue }}')"
                                            @click="open = false"
                                            class="{{ $sort === $optionValue ? 'font-semibold text-[#99745c]' : 'text-[#4b4c53]' }} text-base text-left">
                                            {{ $optionLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    {{-- RIGHT: (optional space) --}}
                    {{-- <div class="...">...</div> --}}
                </div>

                {{-- CATEGORY PILLS --}}
                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    {{-- <button type="button" wire:click="$set('categoryId', null)"
                        class="whitespace-nowrap rounded-full px-4 py-3 text-sm font-medium {{ $categoryId ? 'bg-white text-[#4b4c53]' : 'bg-[#99745c] text-white' }}">
                        {{ __('web.courses.featured_label') }}
                    </button> --}}

                    @foreach ($categories as $category)
                        <button type="button" wire:click="$set('categoryId', {{ $category->id }})"
                            class="whitespace-nowrap rounded-full px-4 py-3 text-sm font-medium {{ (int) $categoryId === (int) $category->id ? 'bg-[#99745c] text-white' : 'bg-white text-[#4b4c53]' }}">
                            {{ $category->name }}
                        </button>
                    @endforeach

                    <a href="{{ route('bundles.index') }}"
                        class="whitespace-nowrap rounded-full px-4 py-3 text-sm font-medium bg-white text-[#4b4c53]">
                        {{ __('web.courses.bundles_label') }}
                    </a>
                </div>

                {{-- SIDEBAR + GRID (THIS IS THE IMPORTANT PART) --}}
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                    {{-- LEFT: Filters sidebar --}}
                    @if ($showFilters)
                        <aside class="w-full lg:w-[276px] lg:shrink-0 lg:sticky lg:top-6">
                            <div class="rounded-xl border border-[#ced0d3] bg-white px-4 py-3 shadow-sm">
                                <div class="flex items-center justify-between gap-2 border-b border-[#ebebeb] pb-4">
                                    <p class="text-base font-semibold text-left text-[#000a19]">
                                        {{ __('web.filters.title') }}</p>
                                    <button type="button" wire:click="resetFilters"
                                        class="flex items-center gap-1 text-sm font-bold text-left text-[#99745c]">
                                        <x-icon.x class="h-5 w-5 text-[#99745c]" />
                                        <span>{{ __('web.filters.reset') }}</span>
                                    </button>
                                </div>

                                <div class="mt-5 flex flex-col gap-5">
                                    {{-- Categories --}}
                                    <div class="flex flex-col gap-5">
                                        <div class="flex items-center justify-between gap-5">
                                            <p class="text-lg font-bold text-left text-black">
                                                {{ __('web.filters.categories_label') }}</p>
                                            <x-icon.chevron-down class="h-6 w-6 text-black" />
                                        </div>

                                        <div class="flex flex-col gap-4">
                                            @foreach ($categories as $category)
                                                <button type="button"
                                                    wire:click="$set('categoryId', {{ $category->id }})"
                                                    class="flex w-full items-center justify-between gap-2.5 text-left">
                                                    <span class="flex items-center gap-2.5">
                                                        @if ((int) $categoryId === (int) $category->id)
                                                            <svg width="24" height="24" viewBox="0 0 24 24"
                                                                fill="none" aria-hidden="true">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M18 2H6C3.79086 2 2 3.79086 2 6V18C2 20.2091 3.79086 22 6 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2ZM16.592 9.46049C16.8463 9.13353 16.7874 8.66232 16.4605 8.40802C16.1335 8.15372 15.6623 8.21262 15.408 8.53958L11.401 13.6914C11.3119 13.806 11.1443 13.8209 11.0364 13.7238L8.50173 11.4426C8.19385 11.1655 7.71963 11.1904 7.44254 11.4983C7.16544 11.8062 7.1904 12.2804 7.49828 12.5575L10.033 14.8387C10.7881 15.5183 11.9613 15.4143 12.585 14.6123L16.592 9.46049Z"
                                                                    fill="#99745C"></path>
                                                            </svg>
                                                        @else
                                                            <svg width="24" height="24" viewBox="0 0 24 24"
                                                                fill="none" aria-hidden="true">
                                                                <path
                                                                    d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 22 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                                    stroke="#A8A7AE" stroke-opacity="0.8"
                                                                    stroke-width="1.5" stroke-linecap="round"
                                                                    stroke-linejoin="round"></path>
                                                            </svg>
                                                        @endif

                                                        <span
                                                            class="{{ (int) $categoryId === (int) $category->id ? 'font-semibold' : '' }} text-base text-black">
                                                            {{ $category->name }}
                                                        </span>
                                                    </span>

                                                    @if (isset($category->courses_count))
                                                        <span
                                                            class="text-base text-black">({{ $category->courses_count }})</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>

                                        <div class="h-px w-full bg-[#a8a7ae]/40"></div>
                                    </div>

                                    {{-- Prices --}}
                                    <div class="flex flex-col gap-5">
                                        <div class="flex items-center justify-between gap-5">
                                            <p class="text-lg font-bold text-left text-black">
                                                {{ __('web.filters.prices_label') }}</p>
                                            <x-icon.chevron-down class="h-6 w-6 text-black" />
                                        </div>

                                        <div class="flex flex-col gap-4">
                                            <button type="button" wire:click="setPriceRange(null, null)"
                                                class="flex w-full items-center justify-between gap-2.5 text-left">
                                                <span class="flex items-center gap-2.5">
                                                    @if ($priceAll)
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M18 2H6C3.79086 2 2 3.79086 2 6V18C2 20.2091 3.79086 22 6 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2ZM16.592 9.46049C16.8463 9.13353 16.7874 8.66232 16.4605 8.40802C16.1335 8.15372 15.6623 8.21262 15.408 8.53958L11.401 13.6914C11.3119 13.806 11.1443 13.8209 11.0364 13.7238L8.50173 11.4426C8.19385 11.1655 7.71963 11.1904 7.44254 11.4983C7.16544 11.8062 7.1904 12.2804 7.49828 12.5575L10.033 14.8387C10.7881 15.5183 11.9613 15.4143 12.585 14.6123L16.592 9.46049Z"
                                                                fill="#99745C"></path>
                                                        </svg>
                                                    @else
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path
                                                                d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 2 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                                stroke="#A8A7AE" stroke-opacity="0.8"
                                                                stroke-width="1.5" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </svg>
                                                    @endif
                                                    <span
                                                        class="{{ $priceAll ? 'font-semibold' : '' }} text-base text-black">{{ __('web.filters.all') }}</span>
                                                </span>
                                            </button>

                                            <button type="button" wire:click="setPriceRange(500, null)"
                                                class="flex w-full items-center justify-between gap-2.5 text-left">
                                                <span class="flex items-center gap-2.5">
                                                    @if ($priceAbove)
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M18 2H6C3.79086 2 2 3.79086 2 6V18C2 20.2091 3.79086 22 6 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2ZM16.592 9.46049C16.8463 9.13353 16.7874 8.66232 16.4605 8.40802C16.1335 8.15372 15.6623 8.21262 15.408 8.53958L11.401 13.6914C11.3119 13.806 11.1443 13.8209 11.0364 13.7238L8.50173 11.4426C8.19385 11.1655 7.71963 11.1904 7.44254 11.4983C7.16544 11.8062 7.1904 12.2804 7.49828 12.5575L10.033 14.8387C10.7881 15.5183 11.9613 15.4143 12.585 14.6123L16.592 9.46049Z"
                                                                fill="#99745C"></path>
                                                        </svg>
                                                    @else
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path
                                                                d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 2 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                                stroke="#A8A7AE" stroke-opacity="0.8"
                                                                stroke-width="1.5" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </svg>
                                                    @endif
                                                    <span
                                                        class="{{ $priceAbove ? 'font-semibold' : '' }} text-base text-black">{{ __('web.filters.price_above_500') }}</span>
                                                </span>
                                            </button>

                                            <button type="button" wire:click="setPriceRange(0, 500)"
                                                class="flex w-full items-center justify-between gap-2.5 text-left">
                                                <span class="flex items-center gap-2.5">
                                                    @if ($priceUnder)
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                d="M18 2H6C3.79086 2 2 3.79086 2 6V18C2 20.2091 3.79086 22 6 22H18C20.2091 22 22 20.2091 22 18V6C22 3.79086 20.2091 2 18 2ZM16.592 9.46049C16.8463 9.13353 16.7874 8.66232 16.4605 8.40802C16.1335 8.15372 15.6623 8.21262 15.408 8.53958L11.401 13.6914C11.3119 13.806 11.1443 13.8209 11.0364 13.7238L8.50173 11.4426C8.19385 11.1655 7.71963 11.1904 7.44254 11.4983C7.16544 11.8062 7.1904 12.2804 7.49828 12.5575L10.033 14.8387C10.7881 15.5183 11.9613 15.4143 12.585 14.6123L16.592 9.46049Z"
                                                                fill="#99745C"></path>
                                                        </svg>
                                                    @else
                                                        <svg width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" aria-hidden="true">
                                                            <path
                                                                d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 2 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                                stroke="#A8A7AE" stroke-opacity="0.8"
                                                                stroke-width="1.5" stroke-linecap="round"
                                                                stroke-linejoin="round"></path>
                                                        </svg>
                                                    @endif
                                                    <span
                                                        class="{{ $priceUnder ? 'font-semibold' : '' }} text-base text-black">{{ __('web.filters.price_under_500') }}</span>
                                                </span>
                                            </button>
                                        </div>

                                        <div class="h-px w-full bg-[#a8a7ae]/40"></div>
                                    </div>

                                    {{-- (Disabled sections) --}}
                                    <div class="flex flex-col gap-5 opacity-50">
                                        <div class="flex items-center justify-between gap-5">
                                            <p class="text-lg font-bold text-left text-black">
                                                {{ __('web.filters.levels_label') }}</p>
                                            <x-icon.chevron-down class="h-6 w-6 text-black" />
                                        </div>
                                        <div class="flex flex-col gap-4">
                                            @foreach ([__('web.courses.level_all'), __('web.courses.level_beginner'), __('web.courses.level_intermediate'), __('web.courses.level_expert')] as $levelLabel)
                                                <div class="flex items-center gap-2.5 text-base text-black">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" aria-hidden="true">
                                                        <path
                                                            d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 2 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                            stroke="#A8A7AE" stroke-opacity="0.8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                    <span>{{ $levelLabel }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="h-px w-full bg-[#a8a7ae]/40"></div>
                                    </div>

                                    <div class="flex flex-col gap-5 opacity-50">
                                        <div class="flex items-center justify-between gap-5">
                                            <p class="text-lg font-bold text-left text-black">
                                                {{ __('web.filters.ratings_label') }}</p>
                                            <x-icon.chevron-down class="h-6 w-6 text-black" />
                                        </div>
                                        <div class="flex flex-col gap-4">
                                            @foreach ([__('web.filters.rating_5'), __('web.filters.rating_4_up'), __('web.filters.rating_3_up')] as $ratingLabel)
                                                <div class="flex items-center gap-2.5 text-base text-black">
                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" aria-hidden="true">
                                                        <path
                                                            d="M6 2H18C20.2091 2 22 3.79086 22 6V18C22 20.2091 20.2091 22 18 22H6C3.79086 2 2 20.2091 2 18V6C2 3.79086 3.79086 2 6 2Z"
                                                            stroke="#A8A7AE" stroke-opacity="0.8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                    <span>{{ $ratingLabel }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Footer buttons --}}
                                    <div
                                        class="flex items-center justify-between gap-3 border-t border-[#ebebeb] pt-4">
                                        <button type="button" wire:click="$toggle('showFilters')"
                                            class="rounded-[100px] border border-[#e1e1e1] px-4 py-3.5 text-sm font-bold text-[#868686]">
                                            {{ __('web.filters.cancel') }}
                                        </button>
                                        <button type="button" wire:click="$refresh"
                                            class="rounded-[100px] bg-[#99745c] px-4 py-3.5 text-sm font-bold text-white">
                                            {{ __('web.filters.apply_filter') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    @endif

                    {{-- RIGHT: Courses grid --}}
                    <div class="min-w-0 flex-1 ">
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            @forelse ($cards as $card)
                                <x-course-card :card="$card" />
                            @empty
                                <div
                                    class="col-span-full rounded-2xl border border-dashed border-[#ced0d3] bg-white p-8 text-center text-sm text-[#71757a]">
                                    {{ __('web.courses.empty') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- PAGINATION (still visible when filters open/close) --}}
                <div>
                    {{ $paginator->links('components.pagination.courses') }}
                </div>
            </div>
        </div>
    </section>
</div>
