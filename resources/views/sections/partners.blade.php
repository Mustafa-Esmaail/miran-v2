@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $logos = $getValue('logos', []);
    $logos = is_array($logos) ? $logos : [];
    $layout = $getValue('layout', 'marquee');
    $logos = collect($logos)
        ->map(function ($logo) {
            return str_starts_with($logo, 'http') ? $logo : \App\Support\S3Url::temporary($logo);
        })
        ->filter()
        ->values()
        ->all();
@endphp

@php
    $fallback = [
        'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
        'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
        'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
    ];
    $renderLogos = $logos !== [] ? $logos : $fallback;
@endphp

@if ($layout === 'grid')
    <section class="border-y border-[#f1f1f1] bg-white py-6 full-bleed">
        <div class="app-container">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-primary sm:text-3xl">
                    {{ $getText('title', $section->title ?? __('web.home.partners_title')) }}
                </h2>
            </div>
            <div class="mt-10">
                <div class="swiper partners-swiper">
                    <div class="swiper-wrapper">
                        @foreach ($renderLogos as $logo)
                            <div class="swiper-slide flex h-14 items-center justify-center">
                                <img src="{{ $logo }}" alt="{{ __('web.sections.partners.logo_alt') }}"
                                    class="partner-logo h-12">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@else
    <section id="partners" class="section-shell">
        <div class="section-title">
            <h2>{{ $getText('title', $section->title ?? __('web.home.partners_title')) }}</h2>
            <p>{{ $getText('subtitle', $section->subtitle ?? __('web.home.partners_subtitle')) }}</p>
        </div>
        <div class="mt-8 overflow-hidden rounded-3xl border border-default bg-surface px-6 py-8">
            <div class="swiper partners-swiper">
                <div class="swiper-wrapper">
                    @foreach ($renderLogos as $logo)
                        <div class="swiper-slide flex h-16 items-center justify-center">
                            <img src="{{ $logo }}" alt="{{ __('web.sections.partners.logo_alt') }}" class="partner-logo">
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

@once('partners-swiper-assets')
    <style>
        .partners-swiper .swiper-slide {
            width: auto;
        }

        @media (prefers-reduced-motion: reduce) {
            .partners-swiper {
                scroll-behavior: auto;
            }
        }
    </style>
    <script>
        function _initPartnersSwiper() {
            document.querySelectorAll('.partners-swiper').forEach((element) => {
                const slideCount = element.querySelectorAll('.swiper-slide').length;
                const canLoop = slideCount > 5;
                new Swiper(element, {
                    loop: canLoop,
                    speed: 5000,
                    autoplay: canLoop ? {
                        delay: 0,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    } : false,
                    allowTouchMove: true,
                    slidesPerView: 2,
                    spaceBetween: 20,
                    breakpoints: {
                        640: {
                            slidesPerView: 3,
                            spaceBetween: 24,
                        },
                        1024: {
                            slidesPerView: 5,
                            spaceBetween: 32,
                        },
                    },
                });
            });
        }
        if (window.Swiper) {
            _initPartnersSwiper();
        } else {
            document.addEventListener('swiper:ready', _initPartnersSwiper);
        }
    </script>
@endonce
