@php
    $locale = app()->getLocale();

    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);

    $layout = $getValue('layout', 'default');
    $image = $getValue('image', null);
    $stats = $getValue('stats', []);
    $stats = is_array($stats) ? $stats : [];
    $slides = $getValue('slides', []);
    $slides = is_array($slides) ? $slides : [];

    // Load background image from backend or fallback to asset
    $bgImageUrl = $image
        ? (str_starts_with($image, 'http')
            ? $image
            : \App\Support\S3Url::temporary($image))
        : asset('img/hero-home.png');

    $imageUrl = $bgImageUrl;
    $heroAboutAlt = __('web.about.hero_image_alt');
    $heroLevelLabel = __('web.about.level_label');

    $heroDefaultHeading = __('web.home.hero_default_heading');
    $heroDefaultSubheading = __('web.home.hero_default_subheading');
@endphp

@if ($layout === 'about')
    <section class="relative overflow-hidden text-white bg-brand-dark full-bleed">
        <div class="pointer-events-none absolute inset-0 hero-grid-overlay opacity-[0.30]"></div>
        <div
            class="pointer-events-none absolute -top-32 -start-56 h-[520px] w-[520px] rounded-full blur-2xl hero-teal-glow">
        </div>

        <div class="relative py-6 app-container">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-10">
                    <div class="space-y-5">
                        <h1 class="text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                            {{ $getText('heading', $section->title ?? '') }}
                        </h1>
                        <p class="max-w-xl text-sm text-hero-muted sm:text-base">
                            {{ $getText('subheading', $section->subtitle ?? '') }}
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-6 px-6 py-5 border rounded-2xl border-white/10 bg-white/5">
                        @foreach ($stats as $index => $stat)
                            <div class="min-w-[140px] flex-1 text-center">
                                <p class="text-2xl font-semibold">{{ $stat['value'] ?? '' }}</p>
                                <p class="mt-1 text-sm text-hero-muted">
                                    {{ $stat["label_{$locale}"] ?? ($stat['label_en'] ?? ($stat['label_ar'] ?? '')) }}
                                </p>
                            </div>
                            @if ($index < count($stats) - 1)
                                <div class="hidden w-px h-12 bg-white/15 sm:block"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="relative w-full max-w-md mx-auto lg:max-w-none">
                    <div class="pointer-events-none absolute -top-10 -end-8 h-40 w-40 rounded-tr-[50px] bg-[#ff8057]">
                    </div>
                    <div class="pointer-events-none absolute -bottom-10 start-8 h-56 w-56 rounded-full bg-[#ffd05b]">
                    </div>
                    <div class="pointer-events-none absolute -bottom-4 start-24 h-44 w-44 rounded-full bg-[#ff8057]/40">
                    </div>

                    <img src="{{ $imageUrl }}" alt="{{ $heroAboutAlt }}"
                        class="relative z-10 h-[420px] w-full rounded-3xl object-cover shadow-card sm:h-[480px]" />

                    <div class="absolute z-20 px-5 py-4 bg-white -bottom-6 start-6 rounded-2xl shadow-card">
                        <p class="text-sm font-medium text-muted">{{ $heroLevelLabel }}</p>
                        <div
                            class="mt-3 h-2 w-48 rounded-full bg-gradient-to-r from-[#99745c] via-[#c0945b]/70 to-transparent">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@elseif ($layout === 'complaint')
    <section class="relative overflow-hidden text-white bg-brand-dark full-bleed">
        <div class="pointer-events-none absolute inset-0 hero-grid-overlay opacity-[0.30]"></div>


        <svg width="68" height="74" viewBox="0 0 68 74" fill="none" xmlns="http://www.w3.org/2000/svg"
            class="pointer-events-none absolute left-1/3 top-1/3 h-[73.43px] w-[67.03px] -translate-x-[150px] -translate-y-1/3"
            preserveAspectRatio="none">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M22.4259 68.5278C16.0259 66.7318 9.32534 65.8258 2.82534 64.9958C1.42534 64.8218 0.125535 65.7928 0.0255346 67.1608C-0.174465 68.5298 0.826121 69.7818 2.12612 69.9557C8.42612 70.7548 14.9255 71.6097 21.0255 73.3387C22.3255 73.7137 23.7261 72.9418 24.1261 71.6138C24.5261 70.2868 23.7259 68.9038 22.4259 68.5278Z"
                fill="url(#paint0_linear_pagehero)"></path>
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M41.8251 43.0644C31.5251 32.5534 19.9251 23.3954 9.8251 12.6024C8.9251 11.5944 7.3251 11.5404 6.3251 12.4814C5.3251 13.4234 5.22549 15.0074 6.22549 16.0154C16.3255 26.8394 27.9255 36.0274 38.2255 46.5694C39.2255 47.5534 40.8251 47.5674 41.8251 46.5994C42.7251 45.6324 42.8251 44.0484 41.8251 43.0644Z"
                fill="url(#paint1_linear_pagehero)"></path>
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M61.1274 2.63576C61.4274 8.65176 61.7268 14.6678 62.0268 20.6848C62.0268 22.0628 63.2274 23.1268 64.6274 23.0598C66.0274 22.9918 67.0268 21.8188 67.0268 20.4398C66.7268 14.4138 66.4274 8.38876 66.1274 2.36376C66.0274 0.985757 64.8272 -0.0712432 63.4272 0.00375683C62.1272 0.0787568 61.0274 1.25876 61.1274 2.63576Z"
                fill="url(#paint2_linear_pagehero)"></path>
            <defs>
                <linearGradient id="paint0_linear_pagehero" x1="12.1148" y1="64.9756" x2="12.1148" y2="73.4335"
                    gradientUnits="userSpaceOnUse">
                    <stop stop-color="var(--banner-grid-start)"></stop>
                    <stop offset="1" stop-color="var(--banner-grid-end)"></stop>
                </linearGradient>
                <linearGradient id="paint1_linear_pagehero" x1="24.0308" y1="11.8096" x2="24.0308" y2="47.3165"
                    gradientUnits="userSpaceOnUse">
                    <stop stop-color="var(--banner-grid-start)"></stop>
                    <stop offset="1" stop-color="var(--banner-grid-end)"></stop>
                </linearGradient>
                <linearGradient id="paint2_linear_pagehero" x1="64.074" y1="0" x2="64.074" y2="23.0628"
                    gradientUnits="userSpaceOnUse">
                    <stop stop-color="var(--banner-grid-start)"></stop>
                    <stop offset="1" stop-color="var(--banner-grid-end)"></stop>
                </linearGradient>
            </defs>
        </svg>
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"
            class="pointer-events-none absolute left-[25%] top-[20%] h-8 w-8" preserveAspectRatio="none">
            <path
                d="M16 0L16.9064 5.50311C17.7172 10.4251 21.5749 14.2828 26.4969 15.0936L32 16L26.4969 16.9064C21.5749 17.7172 17.7172 21.5749 16.9064 26.4969L16 32L15.0936 26.4969C14.2828 21.5749 10.4251 17.7172 5.50311 16.9064L0 16L5.50311 15.0936C10.4251 14.2828 14.4251 10.4251 15.0936 5.50311L16 0Z"
                fill="var(--banner-sparkle)"></path>
        </svg>
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"
            class="pointer-events-none absolute right-[20%] bottom-[20%] h-10 w-10" preserveAspectRatio="none">
            <path
                d="M20 0L21.1331 6.87889C22.1465 13.0314 26.9686 17.8535 33.1211 18.8669L40 20L33.1211 21.1331C26.9686 22.1465 22.1465 26.9686 21.1331 33.1211L20 40L18.8669 33.1211C17.8535 26.9686 13.0314 22.1465 6.87889 21.1331L0 20L6.87889 18.8669C13.0314 17.8535 17.8535 13.0314 18.8669 6.87889L20 0Z"
                fill="var(--banner-star)"></path>
        </svg>

        <div class="relative z-10 flex min-h-[412px] items-center justify-center px-4 py-6 text-center">
            <div class="max-w-3xl">
                <h1 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                    {{ $getText('heading', $section->title ?? '') }}
                </h1>
                @if ($getText('subheading'))
                    <p class="mt-4 text-sm text-hero-muted sm:text-base">
                        {{ $getText('subheading') }}
                    </p>
                @endif
            </div>
        </div>
    </section>
@else
    <section class="relative py-0 overflow-hidden text-white bg-center bg-cover bg-brand-dark full-bleed">



        <div class="relative overflow-hidden">
            <div class="swiper hero-swiper">
                <div class="swiper-wrapper">

                    @foreach ($slides as $slide)
                        @php
                            $slideHeading = $slide["heading_{$locale}"] ?? ($slide['heading_en'] ?? '');
                            $slideSubheading = $slide["subheading_{$locale}"] ?? ($slide['subheading_en'] ?? '');
                            $slideImage = $slide['background_image'] ?? null;
                            $slideImage = $slideImage
                                ? (str_starts_with($slideImage, 'http')
                                    ? $slideImage
                                    : \App\Support\S3Url::temporary($slideImage))
                                : $bgImageUrl;
                            $slideActionText =
                                $slide["button_text_{$locale}"] ??
                                ($slide['button_text_en'] ??
                                    ($slide['button_text_ar'] ?? $getText('primary_button_text', '')));
                            $slideActionUrl = $slide['button_url'] ?? $getValue('primary_button_url');
                        @endphp
                        <div class="swiper-slide">

                            <img src="{{ $slideImage }}" alt="{{ $slideHeading }}"
                                class="h-[40vh] w-full object-cover lg:h-[70vh] lg:rounded-2xl" />
                            @if ($slideHeading || $slideSubheading || (filled($slideActionText) && filled($slideActionUrl)))
                                <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/35 to-black/20">
                                    <div
                                        class="app-container flex max-w-[50%] h-full flex-col items-start justify-center space-y-3 p-2 sm:p-6 lg:p-8">
                                        @if ($slideHeading)
                                            @if ($loop->first)
                                                <h1 class="text-lg font-semibold text-white sm:text-xl lg:text-2xl">
                                                    {{ $slideHeading }}
                                                </h1>
                                            @else
                                                <p class="text-lg font-semibold text-white sm:text-xl lg:text-2xl">
                                                    {{ $slideHeading }}
                                                </p>
                                            @endif
                                        @endif
                                        @if ($slideSubheading)
                                            <p class="text-sm text-hero-muted sm:text-base">
                                                {{ $slideSubheading }}
                                            </p>
                                        @endif
                                        @if (filled($slideActionText) && filled($slideActionUrl))
                                            <a href="{{ $slideActionUrl }}"
                                                class="inline-flex w-auto items-center justify-center rounded-full bg-[#99745c] lg:px-5 px-3 py-1 lg:py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                                                {{ $slideActionText }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                </div>
            </div>


        </div>
    </section>
@endif

@once('swiper-cdn-assets')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" onload="document.dispatchEvent(new CustomEvent('swiper:ready'))"></script>
@endonce

@once('hero-slider-script')
    <script>
        function _initHeroSwiper() {
            const slider = document.querySelector('.hero-swiper');
            if (!slider) return;
            const slideCount = slider.querySelectorAll('.swiper-slide').length;
            new Swiper(slider, {
                effect: 'fade',
                fadeEffect: { crossFade: true },
                speed: 700,
                loop: slideCount > 1,
                grabCursor: true,
                autoplay: slideCount > 1 ? {
                    delay: 4000,
                    disableOnInteraction: false,
                } : false,
            });
        }
        if (window.Swiper) {
            _initHeroSwiper();
        } else {
            document.addEventListener('swiper:ready', _initHeroSwiper);
        }
    </script>
@endonce
