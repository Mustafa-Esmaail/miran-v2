@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $categories = app(\App\Repositories\Contracts\CategoryRepositoryInterface::class)->getHomeCategories(4);
    $moreButtonText = $getText('button_text', __('web.home.view_courses'));
    $moreButtonUrl = $getValue('button_url', route('courses.index'));
@endphp

<section id="categories" class="relative py-6">
    <div class="categories-shell mx-auto w-full rounded-3xl bg-surface px-6 py-6 sm:px-10 lg:px-[72px]">
        <div class="relative overflow-hidden">
            <svg width="382" height="422" viewBox="0 0 382 422" fill="none" xmlns="http://www.w3.org/2000/svg"
                class="pointer-events-none absolute -right-32 -top-28 hidden lg:block" preserveAspectRatio="none">
                <g filter="url(#categoriesGlowTop)">
                    <circle cx="376" cy="46" r="336" fill="url(#categoriesGlowTopGradient)"
                        fill-opacity="0.3" />
                </g>
                <defs>
                    <filter id="categoriesGlowTop" x="0" y="-330" width="752" height="752"
                        filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix" />
                        <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                        <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_63_5460" />
                    </filter>
                    <radialGradient id="categoriesGlowTopGradient" cx="0" cy="0" r="1"
                        gradientUnits="userSpaceOnUse" gradientTransform="translate(376 46) rotate(90) scale(336)">
                        <stop stop-color="var(--category-glow-teal)" />
                        <stop offset="0.53" stop-color="var(--category-glow-teal)"
                            stop-opacity="var(--category-glow-mid)" />
                        <stop offset="0.75" stop-color="var(--category-glow-teal)"
                            stop-opacity="var(--category-glow-soft)" />
                        <stop offset="1" stop-color="var(--category-glow-teal)" stop-opacity="0" />
                    </radialGradient>
                </defs>
            </svg>

            <svg width="312" height="362" viewBox="0 0 312 362" fill="none" xmlns="http://www.w3.org/2000/svg"
                class="pointer-events-none absolute -left-44 top-24 hidden lg:block" preserveAspectRatio="none">
                <g filter="url(#categoriesGlowBottom)">
                    <circle cx="-64" cy="376" r="336" fill="url(#categoriesGlowBottomGradient)"
                        fill-opacity="0.3" />
                </g>
                <defs>
                    <filter id="categoriesGlowBottom" x="-440" y="0" width="752" height="752"
                        filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                        <feFlood flood-opacity="0" result="BackgroundImageFix" />
                        <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                        <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_63_5461" />
                    </filter>
                    <radialGradient id="categoriesGlowBottomGradient" cx="0" cy="0" r="1"
                        gradientUnits="userSpaceOnUse" gradientTransform="translate(-64 376) rotate(90) scale(336)">
                        <stop stop-color="var(--category-glow-sand)" />
                        <stop offset="0.53" stop-color="var(--category-glow-sand)"
                            stop-opacity="var(--category-glow-mid)" />
                        <stop offset="0.75" stop-color="var(--category-glow-sand)"
                            stop-opacity="var(--category-glow-soft)" />
                        <stop offset="1" stop-color="var(--category-glow-sand)" stop-opacity="0" />
                    </radialGradient>
                </defs>
            </svg>

            <div class="relative flex flex-col items-center gap-4 text-center">
                <p class="text-[26px] font-bold text-primary sm:text-[32px]">
                    {{ $getText('title', $section->title ?? __('web.home.categories_title')) }}
                    <span class="text-link">{{ __('web.layout.brand') }}</span>
                </p>
                <p class="max-w-4xl text-base text-secondary">
                    {{ $getText('subtitle', $section->subtitle ?? __('web.home.categories_subtitle')) }}
                </p>
            </div>

            <div x-data="miranSlider(3, 4000)" class="mt-10 mb-5">
                <div class="overflow-hidden">
                    <div x-ref="track" class="flex gap-6 transition-[transform] duration-500 ease-in-out">
                        @foreach ($categories as $category)
                            <a href="{{ route('courses.index', ['categoryId' => $category->id]) }}"
                                class="category-card flex min-h-[180px] flex-col items-center justify-center gap-3 rounded-2xl text-center shadow-md transition hover:-translate-y-1 hover:shadow-lg">
                                <div class="category-card__icon flex h-12 w-12 items-center justify-center rounded-[40px]">
                                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="h-9 w-9">
                                        <path
                                            d="M18 10.5V7.5C18 5.85 16.65 4.5 15 4.5H6C4.35 4.5 3 5.85 3 7.5V28.5C3 30.15 4.35 31.5 6 31.5H30C31.65 31.5 33 30.15 33 28.5V13.5C33 11.85 31.65 10.5 30 10.5H18ZM9 28.5H6V25.5H9V28.5ZM9 22.5H6V19.5H9V22.5ZM9 16.5H6V13.5H9V16.5ZM9 10.5H6V7.5H9V10.5ZM15 28.5H12V25.5H15V28.5ZM15 22.5H12V19.5H15V22.5ZM15 16.5H12V13.5H15V16.5ZM15 10.5H12V7.5H15V10.5ZM28.5 28.5H18V25.5H21V22.5H18V19.5H21V16.5H18V13.5H28.5C29.325 13.5 30 14.175 30 15V27C30 27.825 29.325 28.5 28.5 28.5ZM27 16.5H24V19.5H27V16.5ZM27 22.5H24V25.5H27V22.5Z"
                                            fill="url(#categoryIconGradient-{{ $category->id }})" />
                                        <defs>
                                            <linearGradient id="categoryIconGradient-{{ $category->id }}" x1="18"
                                                y1="4.5" x2="18" y2="31.5" gradientUnits="userSpaceOnUse">
                                                <stop stop-color="var(--category-icon-fill-current-start)" />
                                                <stop offset="1" stop-color="var(--category-icon-fill-current-end)" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </div>
                                <p class="category-card__title text-lg font-semibold">
                                    {{ $category->getTranslation('name', $locale) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 flex justify-center gap-2">
                    <template x-for="dot in _dots" :key="dot">
                        <button @click="active = dot; _apply(); _resetTimer()"
                            class="h-2 rounded-full bg-[#99745c] transition-all duration-300"
                            :class="_page === dot ? 'w-6 opacity-100' : 'w-2 opacity-30'">
                        </button>
                    </template>
                </div>
            </div>
            @if (filled($moreButtonText) && filled($moreButtonUrl))
                <div class="relative flex justify-center">
                    <a href="{{ $moreButtonUrl }}"
                        class="inline-flex items-center justify-center rounded-full bg-[#99745c] px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        {{ $moreButtonText }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
