@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $blocks = $getValue('blocks', []);
    $blocks = is_array($blocks) ? $blocks : [];
@endphp

<section class="section-shell">
    <div class="app-container">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-bold text-primary sm:text-3xl">
                {{ $getText('title', $section->title ?? '') }}
            </h2>
            <p class="mt-3 text-sm text-secondary sm:text-base">
                {{ $getText('subtitle', $section->subtitle ?? '') }}
            </p>
        </div>

        <div class="mt-12 space-y-14">
            @foreach ($blocks as $block)
                @php
                    $alignment = $block['alignment'] ?? 'image-left';
                    $image = $block['image'] ?? null;
                    $imageUrl = $image
                        ? (str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image))
                        : 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=800&q=80';
                @endphp
                <div
                    class="grid items-center gap-10 lg:grid-cols-[0.45fr_0.55fr] {{ $alignment === 'image-right' ? 'lg:grid-cols-[0.55fr_0.45fr]' : '' }}"
                >
                    <div class="relative {{ $alignment === 'image-right' ? 'lg:order-last' : '' }}">
                        <div class="pointer-events-none absolute -top-6 -end-6 h-52 w-52 rounded-tl-[90px] bg-[#ffd05b]"></div>
                        <div class="pointer-events-none absolute -bottom-4 start-10 h-40 w-40 rounded-full bg-[#ff8057]/40"></div>
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ __('web.sections.story.image_alt') }}"
                            class="relative z-10 h-[360px] w-full rounded-3xl object-cover shadow-card"
                        />
                    </div>
                    <p class="text-sm text-secondary sm:text-base">
                        {{ $block["body_{$locale}"] ?? $block['body_en'] ?? $block['body_ar'] ?? '' }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>







