@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section class="relative overflow-hidden py-6 full-bleed">

    <svg width="752" height="443" viewBox="0 0 752 443" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-[200px] top-[-100px] hidden lg:block">
        <g filter="url(#testimonialsGlowMid)">
            <circle cx="376" cy="198" r="336" fill="url(#testimonialsGlowMidGradient)" fill-opacity="0.6" />
        </g>
        <defs>
            <filter id="testimonialsGlowMid" x="0" y="-178" width="752" height="752" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_63_6291" />
            </filter>
            <radialGradient id="testimonialsGlowMidGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(376 198) rotate(90) scale(336)">
                <stop stop-color="#719693" />
                <stop offset="0.53" stop-color="#719693" stop-opacity="0.23" />
                <stop offset="0.75" stop-color="#719693" stop-opacity="0.06" />
                <stop offset="1" stop-color="#719693" stop-opacity="0" />
            </radialGradient>
        </defs>
    </svg>

    <svg width="735" height="500" viewBox="0 0 735 334" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-[-360px] top-[100px]  lg:block z-[-1]">
        <g filter="url(#testimonialsGlowBottom)">
            <circle cx="126.5" cy="608.5" r="568.5" fill="url(#testimonialsGlowBottomGradient)"
                fill-opacity="0.24" />
        </g>

    </svg>

    <div class="mx-auto flex w-full max-w-[1304px] flex-col gap-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-10 lg:flex-row lg:items-start" data-slider-scope>
            <div class="flex flex-col gap-6 lg:w-1/2">
                <h2 class="text-3xl font-bold text-[#322f37] sm:text-[42px]">
                    {{ $getText('title', $section->title ?? __('web.home.testimonials_strip_title')) }}
                </h2>
                <p class="text-sm font-medium text-[#8a8394]">
                    {{ $getText('subtitle', $section->subtitle ?? __('web.home.testimonials_strip_subtitle')) }}
                </p>

            </div>

            <div class="min-w-0 lg:w-1/2">
                <div class="relative">
                    <div class="flex gap-4 overflow-x-auto pb-4 [scrollbar-width:] [&::-webkit-scrollbar]:hidden"
                        data-slider>
                        @foreach ($items as $item)
                            @php
                                $text = $item["text_{$locale}"] ?? ($item['text_en'] ?? ($item['text_ar'] ?? ''));
                                $name = $item["name_{$locale}"] ?? ($item['name_en'] ?? ($item['name_ar'] ?? ''));
                            @endphp
                            <div class="flex  w-[320px] flex-none flex-col items-center gap-5 rounded-xl bg-white px-5 pb-5 pt-10 shadow-sm z-10    "
                                data-slide>
                                <svg width="16" height="16" viewBox="60 88 100 100" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" class="h-16 w-16"
                                    preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                                    <g>
                                        <rect x="60" y="88" width="100" height="100" rx="50" fill="white">
                                        </rect>
                                        <rect x="60.5" y="88.5" width="99" height="99" rx="49.5"
                                            stroke="#EBEBEB">
                                        </rect>
                                        <path
                                            d="M110.101 112V130.655C115.256 130.655 119.428 126.483 119.428 121.328C119.428 116.172 115.256 112 110.101 112Z"
                                            fill="#FF9269"></path>
                                        <path
                                            d="M110.1 112C104.944 112 100.772 116.172 100.772 121.328C100.772 126.483 104.944 130.655 110.1 130.655C113.63 130.655 116.486 126.483 116.486 121.328C116.486 116.172 113.63 112 110.1 112Z"
                                            fill="#FFB082"></path>
                                        <path
                                            d="M110 134.196V150.61H126.414C126.414 141.545 119.065 134.196 110 134.196Z"
                                            fill="#C20035"></path>
                                        <path
                                            d="M110 134.196C100.935 134.196 93.5859 141.545 93.5859 150.61H122.328C122.329 141.545 116.809 134.196 110 134.196Z"
                                            fill="#FF0F47"></path>
                                        <path
                                            d="M115 157.416L112.606 159.747L113.11 162.979L110.052 161.522L109 158.055L110.052 153.979L111.631 156.876L115 157.416Z"
                                            fill="#F3A933"></path>
                                        <path
                                            d="M110 153.979V161.522L106.91 162.979L107.418 159.747L105 157.416L108.405 156.876L110 153.979Z"
                                            fill="#FFBE35"></path>
                                        <path
                                            d="M127 157.416L124.606 159.747L125.11 162.979L122.052 161.522L121 158.055L122.052 153.979L123.631 156.876L127 157.416Z"
                                            fill="#F3A933"></path>
                                        <path
                                            d="M122 153.979V161.522L118.91 162.979L119.418 159.747L117 157.416L120.405 156.876L122 153.979Z"
                                            fill="#FFBE35"></path>
                                        <path
                                            d="M103 157.416L100.606 159.747L101.11 162.979L98.0521 161.522L97 158.055L98.0521 153.979L99.631 156.876L103 157.416Z"
                                            fill="#F3A933"></path>
                                        <path
                                            d="M98 153.979V161.522L94.9102 162.979L95.4184 159.747L93 157.416L96.405 156.876L98 153.979Z"
                                            fill="#FFBE35"></path>
                                    </g>
                                </svg>
                                <div class="flex flex-col items-center gap-3 text-center">
                                    <p class="text-sm font-bold text-[#322f37]">{{ $name }}</p>
                                    <div class="flex items-center justify-center gap-2 rounded-lg">
                                        @for ($i = 0; $i < 5; $i++)
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                class="h-4 w-4">
                                                <path
                                                    d="M12.3223 11.1782C11.8981 11.2796 11.4553 11.3333 11 11.3333C7.87043 11.3333 5.33337 8.79628 5.33337 5.66667C5.33337 5.34436 5.36028 5.02832 5.41198 4.7207C5.38785 4.74391 5.36292 4.76528 5.33667 4.78521C5.1496 4.92722 4.91661 4.97994 4.45063 5.08537L4.0264 5.18136C2.38661 5.55237 1.56672 5.73788 1.37165 6.36516C1.17659 6.99243 1.73554 7.64605 2.85344 8.95329L3.14265 9.29149C3.46032 9.66296 3.61916 9.8487 3.69062 10.0785C3.76207 10.3083 3.73806 10.5561 3.69003 11.0517L3.64631 11.5029C3.47729 13.2471 3.39279 14.1191 3.90347 14.5068C4.41416 14.8945 5.18182 14.541 6.71715 13.8341L7.11437 13.6512C7.55065 13.4503 7.76881 13.3499 8.00004 13.3499C8.23128 13.3499 8.44942 13.4503 8.88572 13.6512L9.28293 13.8341C10.8183 14.541 11.5859 14.8945 12.0966 14.5068C12.6073 14.1191 12.5228 13.2471 12.3538 11.5029L12.3223 11.1782Z"
                                                    fill="#E0AF02" />
                                                <path opacity="0.5"
                                                    d="M6.10205 3.60575L5.88359 3.99765C5.64363 4.42812 5.52365 4.64335 5.33657 4.78537C5.36283 4.76544 5.38776 4.74407 5.41188 4.72087C5.36019 5.02849 5.33328 5.34452 5.33328 5.66683C5.33328 8.79645 7.87033 11.3335 10.9999 11.3335C11.4552 11.3335 11.898 11.2798 12.3222 11.1784L12.31 11.0519C12.2619 10.5562 12.2379 10.3084 12.3094 10.0786C12.3808 9.84886 12.5397 9.66312 12.8573 9.29164L13.1465 8.95345C14.2644 7.64621 14.8234 6.99259 14.6283 6.36532C14.4333 5.73804 13.6134 5.55253 11.9736 5.18152L11.5494 5.08553C11.0834 4.9801 10.8504 4.92738 10.6633 4.78537C10.4762 4.64335 10.3563 4.42812 10.1163 3.99765L9.89784 3.60575C9.0534 2.09091 8.63119 1.3335 7.99995 1.3335C7.3687 1.3335 6.94649 2.09091 6.10205 3.60575Z"
                                                    fill="#E0AF02" />
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <svg width="296" height="1" viewBox="0 0 296 1" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" class="opacity-30" preserveAspectRatio="none">
                                    <line opacity="0.3" x1="0" y1="0.5" x2="296"
                                        y2="0.5" stroke="#A79FB3" />
                                </svg>
                                <p class="text-sm font-medium text-center text-[#322f37]">
                                    {{ $text }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
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

                const getOffset = () => {
                    if (!isRtl) {
                        return slider.scrollLeft;
                    }

                    return slider.scrollWidth - slider.clientWidth - slider.scrollLeft;
                };

                const setOffset = (offset) => {
                    const maxOffset = slider.scrollWidth - slider.clientWidth;
                    const clamped = Math.max(0, Math.min(offset, maxOffset));
                    const target = isRtl ? maxOffset - clamped : clamped;

                    slider.scrollTo({
                        left: target,
                        behavior: 'smooth',
                    });
                };

                const scrollBy = (direction) => {
                    setOffset(getOffset() + direction * step);
                };

                prev?.addEventListener('click', (event) => {
                    event.preventDefault();
                    scrollBy(-1);
                });
                next?.addEventListener('click', (event) => {
                    event.preventDefault();
                    scrollBy(1);
                });
            });
        })();
    </script>
@endonce
