@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section class="relative overflow-hidden py-6 full-bleed">
    <svg width="638" height="501" viewBox="0 0 638 501" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute right-[-70px] top-[0px] hidden lg:block">
        <g filter="url(#trainerQuotesGlowTop)">
            <circle cx="608.5" cy="-107.5" r="568.5" fill="url(#trainerQuotesGlowTopGradient)"
                fill-opacity="0.4" />
        </g>
        <defs>
            <filter id="trainerQuotesGlowTop" x="0" y="-716" width="1217" height="1217" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_63_6232" />
            </filter>
            <radialGradient id="trainerQuotesGlowTopGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(608.5 -107.5) rotate(90) scale(568.5)">
                <stop stop-color="#719693" />
                <stop offset="0.53" stop-color="#719693" stop-opacity="0.23" />
                <stop offset="0.75" stop-color="#719693" stop-opacity="0.06" />
                <stop offset="1" stop-color="#719693" stop-opacity="0" />
            </radialGradient>
        </defs>
    </svg>

    <svg width="900" height="700" viewBox="0 0 900 700" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-[0px] top-[140px] hidden lg:block">
        <g filter="url(#trainerQuotesGlowBottom)">
            <circle cx="126.5" cy="608.5" r="568.5" fill="url(#trainerQuotesGlowBottomGradient)"
                fill-opacity="0.24" />
        </g>
        <defs>
            <filter id="trainerQuotesGlowBottom" x="-482" y="0" width="1217" height="1217"
                filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_63_6233" />
            </filter>
            <radialGradient id="trainerQuotesGlowBottomGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(126.5 608.5) rotate(90) scale(568.5)">
                <stop stop-color="#C0945B" />
                <stop offset="0.53" stop-color="#C0945B" stop-opacity="0.23" />
                <stop offset="0.75" stop-color="#C0945B" stop-opacity="0.06" />
                <stop offset="1" stop-color="#C0945B" stop-opacity="0" />
            </radialGradient>
        </defs>
    </svg>

    <div class="mx-auto flex w-full max-w-[1304px] flex-col gap-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-[#322f37] sm:text-[42px]">
                    {{ $getText('title', $section->title ?? __('web.home.trainer_quotes_title')) }}
                </h2>
                <p class="mt-2 text-sm font-medium text-[#8a8394]">
                    {{ $getText('subtitle', $section->subtitle ?? __('web.home.trainer_quotes_subtitle')) }}
                </p>
            </div>
            <div class="flex items-center gap-4  rtl:flex-row-reverse">
                <button type="button" data-slider-prev
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-[#99745c] shadow-sm"
                    aria-label="{{ __('web.home.trainer_quotes_prev_label') }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M14.707 17.707L9 12L14.707 6.293" stroke="#99745C" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" data-slider-next
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-[#99745c] text-white shadow-sm"
                    aria-label="{{ __('web.home.trainer_quotes_next_label') }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M9.293 17.707L15 12L9.293 6.293" stroke="#FFFFFF" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>

    </div>

    <div class="relative left-1/2 right-1/2 -mx-[50vw] w-screen py-6">
        <div class="flex gap-4 overflow-x-auto pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            style="padding-left: max(1rem, calc((100vw - 1304px) / 2 + 1rem)); padding-right: max(1rem, calc((100vw - 1304px) / 2 + 1rem));"
            data-slider>
            @foreach ($items as $item)
                @php
                    $image = $item['image'] ?? null;
                    $image = $image
                        ? (str_starts_with($image, 'http')
                            ? $image
                            : \App\Support\S3Url::temporary($image))
                        : null;
                    $headline = $item["headline_{$locale}"] ?? ($item['headline_en'] ?? ($item['headline_ar'] ?? ''));
                    $words = preg_split('/\s+/', trim($headline));
                    $words = array_values(array_filter($words, fn($word) => $word !== ''));
                    $wordCount = count($words);
                    $headlineStart = $wordCount > 2 ? implode(' ', array_slice($words, 0, $wordCount - 3)) : '';
                    $headlineEnd = $wordCount > 0 ? implode(' ', array_slice($words, -3)) : '';
                @endphp
                <div class="relative flex h-[420px] w-[260px] flex-none flex-col overflow-hidden rounded-[32px]  sm:w-[280px]"
                    data-slide>
                    <div class="h-[240px] w-full overflow-hidden">
                        @if ($image)
                            <img src="{{ $image }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div
                        class="relative flex flex-1 items-start bg-gradient-to-b from-[#374746] to-[#86adab] px-6 pt-12">
                        <p class="text-[26px] leading-tight text-white sm:text-[30px]"
                            style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            @if ($headlineStart !== '')
                                {!! nl2br(e($headlineStart)) !!}
                                <span class="ms-1"></span>
                            @endif
                            <strong class="font-bold">{!! nl2br(e($headlineEnd)) !!}</strong>
                        </p>
                    </div>
                    <div
                        class="absolute left-1/2 top-[240px] flex h-[60px] w-[60px] -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-[0_6px_18px_rgba(0,0,0,0.15)]">
                        <svg width="32" height="32" viewBox="0 0 60 60" fill="none" class="h-8 w-8">
                            <path
                                d="M37.8405 27.7937C39.6088 28.7553 39.6088 31.2444 37.8405 32.206L27.1639 38.0119C25.4453 38.9465 23.3334 37.7301 23.3334 35.8058V24.1939C23.3334 22.2696 25.4453 21.0532 27.1639 21.9877L37.8405 27.7937Z"
                                fill="#99745C" />
                        </svg>
                    </div>
                </div>
            @endforeach


        </div>
    </div>
</section>

@once('section-slider-script')
    <script>
        (() => {
            const sliders = document.querySelectorAll('[data-slider]');
            if (!sliders.length) return;

            const isRtl = document.documentElement.dir === 'rtl';

            sliders.forEach((slider) => {
                const scope = slider.closest('[data-slider-scope]') ?? slider.closest('section');
                if (!scope) return;

                const prev = scope.querySelector('[data-slider-prev]');
                const next = scope.querySelector('[data-slider-next]');
                const firstSlide = slider.querySelector('[data-slide]');
                const styles = window.getComputedStyle(slider);
                const gap = parseFloat(styles.columnGap || styles.gap || 0) || 0;
                const step = firstSlide ? firstSlide.getBoundingClientRect().width + gap : slider.clientWidth;

                const scrollBy = (direction) => {
                    const target = slider.scrollLeft + direction * step;
                    slider.scrollTo({
                        left: target,
                        behavior: 'smooth',
                    });
                };

                prev?.addEventListener('click', (event) => {
                    event.preventDefault();
                    scrollBy(isRtl ? 1 : -1);
                });
                next?.addEventListener('click', (event) => {
                    event.preventDefault();
                    scrollBy(isRtl ? -1 : 1);
                });
            });
        })();
    </script>
@endonce
