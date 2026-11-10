@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $stats = $getValue('stats', []);
    $stats = is_array($stats) ? $stats : [];
    $image = $getValue('image', null);
    $imageUrl = $image
        ? (str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image))
        : 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80';
@endphp

<section class="full-bleed relative overflow-hidden bg-brand-dark text-white">
    <div class="pointer-events-none absolute inset-0 hero-grid-overlay opacity-[0.30]"></div>
    <div class="pointer-events-none absolute -top-32 -start-56 h-[520px] w-[520px] rounded-full blur-2xl hero-teal-glow"></div>

    <div class="app-container relative py-6">
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

                <div class="flex flex-wrap items-center gap-6 rounded-2xl border border-white/10 bg-white/5 px-6 py-5">
                    @foreach ($stats as $index => $stat)
                        <div class="min-w-[140px] flex-1 text-center">
                            <p class="text-2xl font-semibold">{{ $stat['value'] ?? '' }}</p>
                            <p class="mt-1 text-sm text-hero-muted">
                                {{ $stat["label_{$locale}"] ?? $stat['label_en'] ?? $stat['label_ar'] ?? '' }}
                            </p>
                        </div>
                        @if ($index < count($stats) - 1)
                            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="pointer-events-none absolute -top-10 -end-8 h-40 w-40 rounded-tr-[50px] bg-[#ff8057]"></div>
                <div class="pointer-events-none absolute -bottom-10 start-8 h-56 w-56 rounded-full bg-[#ffd05b]"></div>
                <div class="pointer-events-none absolute -bottom-4 start-24 h-44 w-44 rounded-full bg-[#ff8057]/40"></div>

                <img
                    src="{{ $imageUrl }}"
                    alt="{{ __('web.about.hero_image_alt') }}"
                    class="relative z-10 h-[420px] w-full rounded-3xl object-cover shadow-card sm:h-[480px]"
                />

                <div class="absolute -bottom-6 start-6 z-20 rounded-2xl bg-white px-5 py-4 shadow-card">
                    <p class="text-sm font-medium text-muted">{{ __('web.about.level_label') }}</p>
                    <div class="mt-3 h-2 w-48 rounded-full bg-gradient-to-r from-[#99745c] via-[#c0945b]/70 to-transparent"></div>
                </div>
            </div>
        </div>
    </div>
</section>






