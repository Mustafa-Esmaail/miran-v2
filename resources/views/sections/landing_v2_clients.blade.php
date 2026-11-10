@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $title = $section->fieldText('title', $locale, __('web.landing_v2.clients_title'));
    $logos = $section->fieldValue('logos', []);
    $logos = collect(is_array($logos) ? $logos : [])
        ->map(fn ($logo) => LandingV2::imageUrl(is_string($logo) ? $logo : null))
        ->filter()
        ->values();

    // Swiper's free-mode loop needs more slides than fit on screen.
    $slides = $logos->isNotEmpty() ? $logos->concat($logos) : collect();
@endphp

@if ($slides->isNotEmpty())
    <section class="clients" id="clients">
        <div class="wrap">
            <h2>{{ LandingV2::highlight($title) }}</h2>
            <div class="swiper marq" data-lv2-marquee>
                <div class="swiper-wrapper track">
                    @foreach ($slides as $logo)
                        <div class="swiper-slide"><img src="{{ $logo }}" alt="" width="92" height="40" loading="lazy" decoding="async"></div>
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

@once('landing-v2-marquee')
    <script>
        function _initLandingV2Marquee() {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            document.querySelectorAll('[data-lv2-marquee]').forEach((element) => {
                if (!window.Swiper) {
                    element.classList.add('fallback');
                    return;
                }

                new Swiper(element, {
                    loop: true,
                    slidesPerView: 'auto',
                    spaceBetween: 8,
                    speed: reduce ? 0 : 5000,
                    grabCursor: true,
                    freeMode: { enabled: true, momentum: true, momentumRatio: .5 },
                    autoplay: reduce ? false : { delay: 0, disableOnInteraction: false, pauseOnMouseEnter: false },
                });
            });
        }
        if (window.Swiper) {
            _initLandingV2Marquee();
        } else {
            document.addEventListener('swiper:ready', _initLandingV2Marquee);
        }
    </script>
@endonce
