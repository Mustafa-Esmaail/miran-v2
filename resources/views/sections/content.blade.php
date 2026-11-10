@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
@endphp

<section class="section-shell">
    <div class="section-title">
        <h2>{{ $getText('title', $section->title ?? '') }}</h2>
        <p>{{ $getText('subtitle', $section->subtitle ?? '') }}</p>
    </div>
    <div class="card mt-6 p-6">
        <div class="rich-content max-w-none text-sm">
            {!! $getText('body') !!}
        </div>
    </div>
</section>





