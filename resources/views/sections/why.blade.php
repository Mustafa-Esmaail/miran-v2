@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $points = $getValue('points', []);
    $points = is_array($points) ? $points : [];
@endphp

<section id="why-us" class="section-shell">
    <div class="section-title relative flex flex-col justify-center items-center gap-4 outline-transparent text-center">
        <h2>{{ $getText('title', $section->title ?? __('web.home.why_title')) }}</h2>
        <p>{{ $getText('subtitle', $section->subtitle ?? __('web.home.why_subtitle')) }}</p>
    </div>
    <div x-data="miranSlider(4, 4000)" class="mt-6">
        <div class="overflow-hidden">
            <div x-ref="track" class="flex gap-6 transition-[transform] duration-500 ease-in-out">
                @foreach ($points as $point)
                    <div class="bg-surface rounded-2xl space-y-3 p-5 text-center shadow-md">
                        <span class="icon-chip">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3l2.5 5 5.5.8-4 3.9.9 5.6-4.9-2.7-4.9 2.7.9-5.6-4-3.9 5.5-.8L12 3z" />
                            </svg>
                        </span>
                        <div class="text-lg font-semibold text-primary">
                            {{ $point["title_{$locale}"] ?? ($point['title_en'] ?? ($point['title_ar'] ?? '')) }}
                        </div>
                        <p class="text-sm text-secondary">
                            {{ $point["body_{$locale}"] ?? ($point['body_en'] ?? ($point['body_ar'] ?? '')) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-4 flex justify-center gap-2">
            <template x-for="dot in _dots" :key="dot">
                <button @click="active = dot; _apply(); _resetTimer()"
                    class="h-2 rounded-full bg-[#99745c] transition-all duration-300"
                    :class="_page === dot ? 'w-6 opacity-100' : 'w-2 opacity-30'">
                </button>
            </template>
        </div>
    </div>
</section>
