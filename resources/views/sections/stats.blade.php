@php
    $locale = app()->getLocale();
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];

    $parseCount = function (string $value): array {
        if (preg_match('/-?\d[\d,]*\.?\d*/', $value, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return [
                'value' => $value,
                'prefix' => '',
                'suffix' => '',
                'number' => null,
                'decimals' => 0,
            ];
        }

        [$numberRaw, $offset] = $matches[0];
        $prefix = substr($value, 0, $offset);
        $suffix = substr($value, $offset + strlen($numberRaw));
        $number = (float) str_replace(',', '', $numberRaw);
        $decimals = 0;

        if (str_contains($numberRaw, '.')) {
            $decimals = strlen(rtrim(explode('.', $numberRaw)[1] ?? '', '0'));
        }

        return [
            'value' => $value,
            'prefix' => $prefix,
            'suffix' => $suffix,
            'number' => $number,
            'decimals' => $decimals,
        ];
    };
@endphp

<section id="stats" class="relative overflow-hidden border-b border-[#f1f1f1] bg-[#99745c]/0] py-12 full-bleed">
    <svg width="376" height="306" viewBox="0 0 376 306" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute right-0 top-0 hidden lg:block" preserveAspectRatio="none">
        <g filter="url(#statsGlowTop)">
            <circle cx="376" cy="16" r="336" fill="url(#statsGlowTopGradient)" fill-opacity="0.3"></circle>
        </g>
        <defs>
            <filter id="statsGlowTop" x="0" y="-360" width="752" height="752" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"></feBlend>
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_2161_6975"></feGaussianBlur>
            </filter>
            <radialGradient id="statsGlowTopGradient" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse"
                gradientTransform="translate(376 16) rotate(90) scale(336)">
                <stop stop-color="#719693"></stop>
                <stop offset="0.53" stop-color="#719693" stop-opacity="0.23"></stop>
                <stop offset="0.75" stop-color="#719693" stop-opacity="0.06"></stop>
                <stop offset="1" stop-color="#719693" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
    </svg>
    <svg width="376" height="306" viewBox="0 0 376 306" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute bottom-0 left-0 hidden lg:block" preserveAspectRatio="none">
        <g filter="url(#statsGlowBottom)">
            <circle cy="306" r="336" fill="url(#statsGlowBottomGradient)" fill-opacity="0.5"></circle>
        </g>
        <defs>
            <filter id="statsGlowBottom" x="-376" y="-70" width="752" height="752" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix"></feFlood>
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape"></feBlend>
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_2161_6976"></feGaussianBlur>
            </filter>
            <radialGradient id="statsGlowBottomGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(0 306) rotate(90) scale(336)">
                <stop stop-color="#99745C"></stop>
                <stop offset="0.53" stop-color="#99745C" stop-opacity="0.23"></stop>
                <stop offset="0.75" stop-color="#99745C" stop-opacity="0.06"></stop>
                <stop offset="1" stop-color="#99745C" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
    </svg>

    <div class="relative mx-auto flex w-full max-w-[1440px] flex-col items-center gap-12 px-6 lg:px-[250px]">
        @if ($section->title)
            <div class="flex flex-col items-center gap-6 text-center">
                <p class="text-4xl font-bold text-[#99745c]">
                    {{ $section->title }}
                </p>
                <p>{{ $section->subtitle }}</p>
            </div>
        @endif
        <div
            class="flex flex-col items-center gap-5 text-center sm:flex-row sm:flex-wrap sm:justify-center lg:flex-nowrap lg:gap-[195px]">
            @forelse ($items as $item)
                @php
                    $label = $item["label_{$locale}"] ?? ($item['label_en'] ?? ($item['label_ar'] ?? ''));
                    $valueData = $parseCount((string) ($item['value'] ?? ''));
                @endphp
                <div class="flex flex-col items-center gap-6">
                    <p class="text-[50px] font-bold text-[#99745c]">
                        @if ($valueData['number'] !== null)
                            <span class="count-up" data-count-value="{{ $valueData['number'] }}"
                                data-count-prefix="{{ $valueData['prefix'] }}"
                                data-count-suffix="{{ $valueData['suffix'] }}"
                                data-count-decimals="{{ $valueData['decimals'] }}">
                                {{ $valueData['value'] }}
                            </span>
                        @else
                            {{ $valueData['value'] }}
                        @endif
                    </p>
                    <p class="text-2xl font-bold text-[#99745c]">{{ $label }}</p>
                </div>
            @empty
                <div class="flex flex-col items-center gap-6">
                    <p class="text-[50px] font-bold text-[#99745c]">
                        <span class="count-up" data-count-value="3200" data-count-prefix="+" data-count-decimals="0">
                            +3,200
                        </span>
                    </p>
                    <p class="text-2xl font-bold text-[#99745c]">{{ __('web.home.stats.learners') }}</p>
                </div>
                <div class="flex flex-col items-center gap-6">
                    <p class="text-[50px] font-bold text-[#99745c]">
                        <span class="count-up" data-count-value="180" data-count-prefix="+" data-count-decimals="0">
                            +180
                        </span>
                    </p>
                    <p class="text-2xl font-bold text-[#99745c]">{{ __('web.home.stats.entities') }}</p>
                </div>
                <div class="flex flex-col items-center gap-6">
                    <p class="text-[50px] font-bold text-[#99745c]">
                        <span class="count-up" data-count-value="120" data-count-prefix="+" data-count-decimals="0">
                            +120
                        </span>
                    </p>
                    <p class="text-2xl font-bold text-[#99745c]">{{ __('web.home.stats.programs') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

@once('stats-countup')
    <script>
        (() => {
            const elements = document.querySelectorAll('.count-up');
            if (!elements.length) return;

            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const formatValue = (value, decimals) => {
                return new Intl.NumberFormat(undefined, {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                }).format(value);
            };

            const animate = (element) => {
                const target = Number(element.dataset.countValue ?? '0');
                const prefix = element.dataset.countPrefix ?? '';
                const suffix = element.dataset.countSuffix ?? '';
                const decimals = Number(element.dataset.countDecimals ?? '0');

                if (!Number.isFinite(target) || prefersReduced) {
                    element.textContent = `${prefix}${formatValue(target, decimals)}${suffix}`;
                    return;
                }

                const duration = 1200;
                const start = performance.now();

                const step = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = target * eased;
                    element.textContent = `${prefix}${formatValue(current, decimals)}${suffix}`;

                    if (progress < 1) {
                        requestAnimationFrame(step);
                    }
                };

                requestAnimationFrame(step);
            };

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        animate(entry.target);
                        observer.unobserve(entry.target);
                    });
                }, {
                    threshold: 0.4
                }
            );

            elements.forEach((element) => observer.observe(element));
        })();
    </script>
@endonce
