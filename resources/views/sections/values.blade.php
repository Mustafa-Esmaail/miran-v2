@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $cards = $getValue('cards', []);
    $cards = is_array($cards) ? $cards : [];
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

        <div class="mt-12 grid gap-8 lg:grid-cols-2">
            @foreach ($cards as $card)
                <div class="relative rounded-2xl bg-white p-8 shadow-card">
                    <div class="flex items-start gap-5">
                        <div class="flex h-14 w-14 shrink-0  items-center justify-center rounded-xl bg-gradient-to-b from-[#374746] to-[#86adab] text-white">
                            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none">
                                <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-primary">
                                {{ $card["title_{$locale}"] ?? $card['title_en'] ?? $card['title_ar'] ?? '' }}
                            </h3>
                            <p class="mt-2 text-sm text-secondary">
                                {{ $card["body_{$locale}"] ?? $card['body_en'] ?? $card['body_ar'] ?? '' }}


                             </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>





