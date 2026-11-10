@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $image = $getValue(
        'image',
        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
    );
    $image = $image ? (str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image)) : null;
@endphp

<section class="section-shell relative overflow-hidden bg-[#374746] text-white  full-bleed min-h-[45vh]">
    <div class="pointer-events-none absolute inset-0 opacity-[0.12] hero-grid-overlay"></div>
    <div
        class="pointer-events-none absolute -top-[120px] start-[-220px] h-[520px] w-[520px] rounded-full blur-2xl hero-teal-glow">
    </div>
    <div class="pointer-events-none absolute inset-0 hero-frame-overlay opacity-80"></div>
    <div class="app-container relative">
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div>
                <h2 class="text-2xl font-semibold sm:text-3xl">{{ $getText('title', $section->title ?? '') }}</h2>
                <p class="mt-3 max-w-xl text-sm text-white/75 sm:text-base">
                    {{ $getText('text', $section->subtitle ?? '') }}
                </p>

                @if (!empty($getValue('logos')) && is_array($getValue('logos')))
                    <div class="mt-5 flex flex-wrap items-center gap-4">
                        @foreach ($getValue('logos') as $logo)
                            @php
                                $logoUrl = $logo
                                    ? (str_starts_with($logo, 'http')
                                        ? $logo
                                        : \App\Support\S3Url::temporary($logo))
                                    : null;
                            @endphp
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ __('web.sections.cta.logo_alt') }}"
                                    class="h-10 w-auto opacity-80">
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="mt-6">
                    <a href="{{ $getValue('button_url', '#') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-[#99745c] px-6 py-3 text-sm font-semibold text-white">
                        {{ $getText('button_text', __('web.home.cta')) }}
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M21.25 12c0 .2-.07.39-.22.54l-6.07 6.07a.75.75 0 01-1.06-1.06L19.44 12l-5.54-5.54a.75.75 0 011.06-1.06l6.07 6.07c.15.15.22.34.22.53Z"
                                fill="white" />
                            <path d="M21.08 12c0 .41-.34.75-.75.75H3.5a.75.75 0 010-1.5h16.83c.41 0 .75.34.75.75Z"
                                fill="white" />
                        </svg>
                    </a>
                </div>
            </div>

            @if ($image)
                <div class="flex justify-center lg:justify-end">
                    <div class="rounded-3xl border border-white/20 bg-white/5 p-4">
                        <img src="{{ $image }}" alt="{{ __('web.sections.cta.image_alt') }}"
                            class="h-52 w-auto rounded-2xl object-cover sm:h-64">
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
