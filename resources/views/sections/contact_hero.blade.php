@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $cards = $getValue('cards', []);
    $cards = is_array($cards) ? $cards : [];
    $iconFor = function (?string $icon) {
        return match ($icon) {
            'pin' => '<path d="M20 10C20 14.4183 12 22 12 22C12 22 4 14.4183 4 10C4 5.58172 7.58172 2 12 2C16.4183 2 20 5.58172 20 10Z"fill="white"stroke="white"stroke-width="1.5"></path><path d="M12 11C12.5523 11 13 10.5523 13 10C13 9.44772 12.5523 9 12 9C11.4477 9 11 9.44772 11 10C11 10.5523 11.4477 11 12 11Z"fill="#99745C"stroke="#99745C"stroke-width="4"stroke-linecap="round"stroke-linejoin="round"></path>',
            'chat' => '<path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.7-1.5A10 10 0 1012 2Z" fill="white"></path><path d="M7 11h10v2H7Zm0-4h10v2H7Z" fill="#99745C"></path>',
            'phone' => '<path d="M6.6 2h4l1 4-2.4 1.4A12.5 12.5 0 0016.6 14l1.4-2.4 4 1v4c0 1.1-.9 2-2 2A16.6 16.6 0 015.6 4c0-1.1.9-2 2-2Z" fill="white"></path>',
            'clock' => '<circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.5" fill="none"></circle><path d="M12 7v5l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>',
            default => '<path d="M12 2a8 8 0 00-8 8c0 4.4 8 12 8 12s8-7.6 8-12a8 8 0 00-8-8z" fill="white"></path>',
        };
    };
@endphp

<section class="relative overflow-hidden bg-brand-dark text-white full-bleed">
    <div class="pointer-events-none absolute inset-0 hero-grid-overlay opacity-[0.30]"></div>
    <div class="pointer-events-none absolute -top-32 -start-56 h-[520px] w-[520px] rounded-full blur-2xl hero-teal-glow"></div>

    <div class="relative z-10 flex min-h-[520px] flex-col items-center justify-center px-4 py-6 text-center sm:min-h-[600px]">
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

        <div class="mt-10 w-full max-w-[1200px]">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($cards as $card)
                    <div class="rounded-2xl border border-white/60 bg-white/40 p-6 text-white shadow-card">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-accent">
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none">
                                {!! $iconFor($card['icon'] ?? null) !!}
                            </svg>
                        </div>
                        <div class="mt-5 space-y-2 text-start">
                            <p class="text-lg font-semibold">
                                {{ $card["title_{$locale}"] ?? $card['title_en'] ?? $card['title_ar'] ?? '' }}
                            </p>
                            <div class="text-sm text-white/90 prose prose-invert prose-sm max-w-none contact-body" >
                                {!! $card["body_{$locale}"] ?? $card['body_en'] ?? $card['body_ar'] ?? '' !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>




