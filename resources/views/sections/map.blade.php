@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);

    $address = $getText('address', $section->title ?? '');
    $mapUrl = $getText(
        'map_url',
        'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d28979.79707626086!2d46.784125!3d24.779196!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2f01631e327683%3A0x7f884985d75be17a!2z2YXYsdmD2LIg2YXYsdin2YYg2KfZhNmC2KfYr9ipINmE2YTYqtiv2LHZitio!5e0!3m2!1sar!2ssa!4v1771417197049!5m2!1sar!2ssa',
    );
@endphp

<section class="text-center pt-3">
    @if ($address !== '')
        <h2 class="mb-4 text-2xl font-bold text-[#242528]">{{ $address }}</h2>
    @endif

    <div class="overflow-hidden bg-white full-bleed">
        <iframe class="h-[340px] w-full sm:h-[420px]" src="{{ $mapUrl }}" allowfullscreen loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
    </div>
</section>
