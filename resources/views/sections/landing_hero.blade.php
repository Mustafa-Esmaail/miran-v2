@php
    $locale = app()->getLocale();
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);

    $overrideImage = $getValue('override_image');
    $slides = $getValue('slides', []);
    $slides = is_array($slides) ? array_values($slides) : [];

    $fallbackImageUrl = null;
    if ($overrideImage) {
        $fallbackImageUrl = str_starts_with($overrideImage, 'http')
            ? $overrideImage
            : \App\Support\S3Url::temporary($overrideImage);
    } elseif (isset($landable)) {
        $fallbackImageUrl = $landable->imageUrl ?? $landable->image_url ?? null;
    }
    $fallbackImageUrl ??= asset('img/hero-home.png');

    $title = isset($landable)
        ? $landable->getTranslation('title', app()->getLocale())
        : ($section->title ?? '');
@endphp

<section class="relative overflow-hidden text-white full-bleed">
    <div class="swiper landing-hero-swiper">
        <div class="swiper-wrapper">
            @forelse ($slides as $slide)
                @php
                    $slideHeading = $slide["heading_{$locale}"] ?? ($slide['heading_en'] ?? '');
                    $slideSubheading = $slide["subheading_{$locale}"] ?? ($slide['subheading_en'] ?? '');
                    $slideBg = $slide['background_image'] ?? null;
                    $slideBgUrl = $slideBg
                        ? (str_starts_with($slideBg, 'http') ? $slideBg : \App\Support\S3Url::temporary($slideBg))
                        : $fallbackImageUrl;
                    $slideButtonText = $slide["button_text_{$locale}"] ?? ($slide['button_text_en'] ?? ($slide['button_text_ar'] ?? ''));
                    $slideButtonUrl = $slide['button_url'] ?? '';
                @endphp
                <div class="swiper-slide">
                    <img src="{{ $slideBgUrl }}" alt="{{ $slideHeading ?: $title }}"
                         class=" w-full object-cover lg:h-[95vh]" />
                    <div class="absolute inset-0">
                        <div class="app-container flex h-full flex-col items-start justify-center space-y-4 p-4 sm:p-8">
                            @if ($slideHeading)
                                @if ($loop->first)
                                    <h1 class="max-w-2xl text-2xl font-bold text-white sm:text-3xl lg:text-4xl">
                                        {{ $slideHeading }}
                                    </h1>
                                @else
                                    <p class="max-w-2xl text-2xl font-bold text-white sm:text-3xl lg:text-4xl">
                                        {{ $slideHeading }}
                                    </p>
                                @endif
                            @endif
                            @if ($slideSubheading)
                                <p class="max-w-xl text-sm text-white/80 sm:text-base">{{ $slideSubheading }}</p>
                            @endif
                            @if (filled($slideButtonText) && filled($slideButtonUrl))
                                <a href="{{ $slideButtonUrl }}"
                                   class="inline-flex items-center rounded-full bg-brand-accent px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                                    {{ $slideButtonText }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="swiper-slide">
                    <img src="{{ $fallbackImageUrl }}" alt="{{ $title }}"
                         class=" w-full object-cover lg:h-[95vh]" />
                    <div class="absolute inset-0">
                        <div class="app-container flex h-full flex-col items-start justify-center p-4 sm:p-8">
                            <h1 class="max-w-2xl text-2xl font-bold text-white sm:text-3xl lg:text-4xl">
                                {{ $title }}
                            </h1>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

@once('swiper-cdn-assets')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" onload="document.dispatchEvent(new CustomEvent('swiper:ready'))"></script>
@endonce

@once('landing-hero-slider-script')
    <script>
        function _initLandingHeroSwiper() {
            const slider = document.querySelector('.landing-hero-swiper');
            if (!slider) return;
            const slideCount = slider.querySelectorAll('.swiper-slide').length;
            new Swiper(slider, {
                effect: 'fade',
                fadeEffect: { crossFade: true },
                speed: 700,
                loop: slideCount > 1,
                grabCursor: true,
                autoplay: slideCount > 1 ? { delay: 4000, disableOnInteraction: false } : false,
            });
        }
        if (window.Swiper) {
            _initLandingHeroSwiper();
        } else {
            document.addEventListener('swiper:ready', _initLandingHeroSwiper);
        }
    </script>
@endonce
