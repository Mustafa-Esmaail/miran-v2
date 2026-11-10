@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $points = $getValue('points', []);
    $points = is_array($points) ? $points : [];
@endphp

<section id="about" class="section-shell">
    <div class="section-title">
        <h2>{{ $getText('title', $section->title ?? '') }}</h2>
        <p>{{ $getText('subtitle', $section->subtitle ?? '') }}</p>
    </div>
    <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
        <div class="space-y-4 text-sm text-secondary">
            <div class="rich-content">{!! $getText('body') !!}</div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($points as $point)
                <div class="flex flex-col">

                    <div class="card flex items-start gap-3 p-4">
                        <span class="icon-chip">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-primary">
                                {{ $point["title_{$locale}"] ?? $point['title_en'] ?? $point['title_ar'] ?? '' }}
                            </div>
                            <div class="text-xs text-secondary">
                                {{ $point["body_{$locale}"] ?? $point['body_en'] ?? $point['body_ar'] ?? '' }}
                            </div>
                        </div>
                    </div>

               </div>
                @endforeach
            </div>
        </div>
        <div class="card p-6">
            <div class="text-xs font-semibold uppercase text-secondary">{{ $getText('card_label') }}</div>
            <div class="mt-3 text-2xl font-semibold text-primary">{{ $getText('card_title') }}</div>
            <div class="rich-content mt-2 text-sm">{!! $getText('card_body') !!}</div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-button :href="route('courses.index')" variant="primary" size="md">
                    {{ __('web.home.cta') }}
                </x-button>
                <x-button href="#contact" variant="secondary" size="md">
                    {{ __('web.nav.contact_request') }}
                </x-button>
            </div>
        </div>
    </div>
</section>





