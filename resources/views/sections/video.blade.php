@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);

    $videoUrl = $getValue('video_url');
    $thumbnail = $getValue('thumbnail');
    $thumbnail = $thumbnail
        ? (str_starts_with($thumbnail, 'http')
            ? $thumbnail
            : \App\Support\S3Url::temporary($thumbnail))
        : 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80';

    $embedUrl = null;
    if ($videoUrl) {
        if (
            preg_match(
                '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~',
                $videoUrl,
                $matches,
            ) === 1
        ) {
            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=1';
        } else {
            $embedUrl = $videoUrl;
        }
    }
@endphp

<section class="relative overflow-hidden full-bleed min-h-[50vh]  flex items-center justify-center py-6"
    x-data="{ open: false }"
    style="min-height: 50vh">
    {{-- Background --}}
    <div class="pointer-events-none absolute inset-0 bg-cover bg-center"
        style="background-image: url('{{ $thumbnail ?? '' }}');"></div>
    <div class="pointer-events-none absolute inset-0 bg-[#111]/40"></div>

    {{-- Content (centered vertically + horizontally) --}}
    <div class="relative mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8">
        <div class="flex w-full flex-col items-center justify-center gap-6 text-center">
            @if ($section->title || $section->subtitle)
                <div>
                    @if ($section->title)
                        <h2 class="text-3xl font-semibold text-white sm:text-4xl">
                            {{ $getText('title', $section->title) }}
                        </h2>
                    @endif
                    @if ($section->subtitle)
                        <p class="mt-3 text-sm font-medium text-white/80 sm:text-base">
                            {{ $getText('subtitle', $section->subtitle) }}
                        </p>
                    @endif
                </div>
            @endif

            <button type="button"
                class="group flex h-24 w-24 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white backdrop-blur transition hover:scale-105 hover:bg-white/20"
                @if ($embedUrl) @click="open = true" @endif>
                <svg width="92" height="92" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="w-[92px] h-[92px]" preserveAspectRatio="none">
                    <circle cx="46" cy="46" r="46" fill="white"></circle>
                    <path
                        d="M63.0684 41.2346C66.9772 43.3117 66.9772 48.6883 63.0684 50.7653L39.4674 63.3061C35.6685 65.3248 31 62.6973 31 58.5408V33.4592C31 29.3026 35.6685 26.6752 39.4674 28.6939L63.0684 41.2346Z"
                        fill="#99745C"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- Modal --}}
    <div x-cloak x-show="open" style="z-index: 9999; background:#00000075"
        class="fixed inset-0 bg-black flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0" @click="open = false"></div>

        <div class="relative z-[999999] w-full max-w-4xl overflow-hidden rounded-2xl shadow-2xl">
            <button type="button"
                class="absolute end-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white"
                @click="open = false" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>

            @if ($embedUrl)
                <div class="aspect-video w-full">
                    <iframe :src="open ? '{{ $embedUrl }}' : ''" class="h-full w-full" allow="autoplay; fullscreen"
                        allowfullscreen></iframe>
                </div>
            @endif
        </div>
    </div>
</section>
