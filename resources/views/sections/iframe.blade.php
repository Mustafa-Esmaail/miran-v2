@php
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);

    $iframeCode = $getValue('iframe_code', '');
    $heightOverride = $getValue('height');

    if ($heightOverride && $iframeCode) {
        $iframeCode = preg_replace('/height\s*=\s*["\']?\d+["\']?/i', 'height="' . (int) $heightOverride . '"', $iframeCode);
    }
@endphp

@if ($iframeCode)
    <section class="w-full">
        @if ($section->title || $section->subtitle)
            <div class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 text-center">
                @if ($section->title)
                    <h2 class="text-2xl font-semibold sm:text-3xl">{{ $section->title }}</h2>
                @endif
                @if ($section->subtitle)
                    <p class="mt-2 text-gray-600">{{ $section->subtitle }}</p>
                @endif
            </div>
        @endif

        <div class="w-full [&_iframe]:w-full">
            {!! $iframeCode !!}
        </div>
    </section>
@endif
