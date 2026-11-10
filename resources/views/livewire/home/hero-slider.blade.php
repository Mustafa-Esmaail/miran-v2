@php
    $settings = app(\App\Services\Settings\SettingsService::class);
    $locale = app()->getLocale();
    $heroTag = $settings->getTranslatable('home.hero_tag', $locale, __('web.home.hero_tag'));
    $searchPlaceholder = $settings->getTranslatable('home.hero_search_placeholder', $locale, __('web.home.search_courses'));
    $categoryPlaceholder = $settings->getTranslatable('home.hero_category_placeholder', $locale, __('web.home.search_category'));
    $searchCta = $settings->getTranslatable('home.hero_search_cta', $locale, __('web.home.search_cta'));
    $featuredCategory = $categories->first();
@endphp

<section class="relative overflow-hidden text-white bg-hero-gradient hero-full full-bleed">
    {{-- background grid + soft gradients to match Figma hero --}}
    <div class="absolute inset-0 pointer-events-none opacity-10">
        <svg class="w-full h-full" viewBox="0 0 1440 960" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g stroke="white" stroke-width="2">
                <line x1="-1" y1="359" x2="1439" y2="359" />
                <line x1="-1" y1="239" x2="1439" y2="239" />
                <line x1="1320" y1="15" x2="1320" y2="1039" />
                <line x1="0" y1="-85" x2="0" y2="939" />
                <line x1="120" y1="15" x2="120" y2="1039" />
                <line x1="240" y1="-85" x2="240" y2="939" />
                <line x1="360" y1="-85" x2="360" y2="939" />
                <line x1="480" y1="-85" x2="480" y2="939" />
                <line x1="600" y1="-85" x2="600" y2="939" />
                <line x1="720" y1="-85" x2="720" y2="939" />
                <line x1="840" y1="-85" x2="840" y2="939" />
                <line x1="960" y1="-85" x2="960" y2="939" />
                <line x1="1080" y1="-85" x2="1080" y2="939" />
                <line x1="1200" y1="-85" x2="1200" y2="939" />
                <line x1="1440" y1="-85" x2="1440" y2="939" />
                <line x1="-1" y1="839" x2="1439" y2="839" />
                <line x1="-1" y1="719" x2="1439" y2="719" />
                <line x1="-1" y1="959" x2="1439" y2="959" />
                <line x1="-1" y1="599" x2="1439" y2="599" />
                <line x1="-1" y1="479" x2="1439" y2="479" />
                <line x1="-1" y1="119" x2="1439" y2="119" />
                <line x1="-1" y1="-1" x2="1439" y2="-1" />
            </g>
        </svg>
    </div>
    <div class="absolute inset-0 pointer-events-none opacity-60 hero-ambient"></div>

    <div
        x-data="{
            active: 0,
            total: {{ count($slides) }},
            timer: null,
            start() {
                if (this.total < 2) {
                    return;
                }
                this.timer = setInterval(() => {
                    this.active = (this.active + 1) % this.total;
                }, 6500);
            },
            goTo(index) {
                this.active = index;
                if (this.timer) {
                    clearInterval(this.timer);
                    this.start();
                }
            }
        }"
        x-init="start()"
        class="relative z-10"
    >
        <div class="py-6 app-container">
            {{-- center hero copy + search --}}
            <div class="flex flex-col items-center max-w-4xl gap-5 mx-auto text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
                    {{ $heroTag }}
                </p>
                <h1 class="text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl xl:text-[46px] xl:leading-tight">
                    @foreach ($slides as $index => $slide)
                        <span x-show="active === {{ $index }}" x-cloak>
                            {{ $slide['title'] ?? '' }}
                        </span>
                    @endforeach
                </h1>
                <p class="text-sm text-[#E5E6E8] sm:text-base max-w-2xl">
                    @foreach ($slides as $index => $slide)
                        <span x-show="active === {{ $index }}" x-cloak>
                            {{ $slide['subtitle'] ?? '' }}
                        </span>
                    @endforeach
                </p>
                <form method="GET" action="{{ route('courses.index') }}" class="w-full mt-2">
                    <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center">
                    <div class="flex items-center w-full max-w-xl gap-3 px-6 py-3 bg-white rounded-full shadow-card">
                            <svg class="h-6 w-6 text-[#82868E]" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M15.755 14.255H14.965L14.685 13.985C15.665 12.845 16.255 11.365 16.255 9.755C16.255 6.165 13.345 3.255 9.755 3.255C6.165 3.255 3.255 6.165 3.255 9.755C3.255 13.345 6.165 16.255 9.755 16.255C11.365 16.255 12.845 15.665 13.985 14.685L14.255 14.965V15.755L19.255 20.745L20.745 19.255L15.755 14.255ZM9.755 14.255C7.26501 14.255 5.255 12.245 5.255 9.755C5.255 7.26501 7.26501 5.255 9.755 5.255C12.245 5.255 14.255 7.26501 14.255 9.755C14.255 12.245 12.245 14.255 9.755 14.255Z"
                                    fill="currentColor"
                                />
                            </svg>
                            <input
                                type="search"
                                name="search"
                                placeholder="{{ $searchPlaceholder }}"
                                class="w-full border-0 bg-transparent text-sm text-[#82868E] placeholder:text-[#82868E] focus:outline-none"
                            >
                            <select
                                name="categoryId"
                                class="hidden rounded-full border-0 bg-transparent text-sm text-[#82868E] focus:outline-none sm:block"
                            >
                                <option value="">{{ $categoryPlaceholder }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->getTranslation('name', app()->getLocale()) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button
                            type="submit"
                            class="px-8 py-3 text-sm font-semibold text-white rounded-full bg-brand-accent shadow-card hover:brightness-110"
                        >
                            {{ $searchCta }}
                        </button>
                    </div>
                    <div class="mt-3 sm:hidden">
                        <select
                            name="categoryId"
                            class="w-full px-4 py-3 text-sm text-white border rounded-full border-white/20 bg-white/10 placeholder:text-white/70"
                        >
                            <option value="">{{ $categoryPlaceholder }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->getTranslation('name', app()->getLocale()) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- hero visual side: main image + info cards --}}
            <div class="mt-16 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div class="relative hidden lg:block">
                    <div class="relative p-4 overflow-hidden border rounded-2xl border-white/15 bg-white/5 backdrop-blur">
                        @foreach ($slides as $index => $slide)
                            <img
                                src="{{ $slide['image'] ?? '' }}"
                                alt="{{ $slide['title'] ?? '' }}"
                                class="h-[380px] w-full rounded-2xl object-cover transition-opacity duration-700"
                                :class="active === {{ $index }} ? 'opacity-100' : 'opacity-0'"
                                x-cloak
                            />
                        @endforeach
                    </div>
                    <div class="absolute -start-4 bottom-10 rounded-2xl bg-white px-4 py-3 text-[#1E2B27] shadow-card">
                        <p class="text-sm font-semibold">
                            {{ $featuredCategory?->name ?? __('web.home.categories_title') }}
                        </p>
                        <p class="mt-1 text-xs text-[#82868E]">
                            {{ __('web.home.course_count', ['count' => $featuredCategory?->courses_count ?? 120]) }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col gap-4 text-start text-white/85">
                    <div class="p-4 rounded-2xl bg-white/10 shadow-card">
                        <div class="text-sm font-semibold text-white">
                            {{ __('web.home.stats.learners') }}
                        </div>
                        <div class="h-2 mt-2 rounded-full bg-white/20">
                            <div class="w-2/3 h-2 rounded-full bg-brand-accent"></div>
                        </div>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/10 shadow-card">
                        <div class="text-sm font-semibold text-white">
                            {{ __('web.home.stats.programs') }}
                        </div>
                        <p class="mt-2 text-3xl font-semibold text-white">+120</p>
                    </div>
                </div>
            </div>
        </div>

        @if (count($slides) > 1)
            <div class="absolute z-10 flex items-center gap-2 -translate-x-1/2 bottom-8 start-1/2">
                @foreach ($slides as $index => $slide)
                    <button
                        type="button"
                        class="h-2.5 w-2.5 rounded-full border border-white/70"
                        :class="active === {{ $index }} ? 'bg-white' : 'bg-white/30'"
                        x-on:click="goTo({{ $index }})"
                        aria-label="{{ __('web.home.hero_slide_aria', ['number' => $index + 1]) }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
