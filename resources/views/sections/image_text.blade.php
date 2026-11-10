@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $image = $getValue('image', null);
    $image = $image ? (str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image)) : null;
    $reverse = ($getValue('image_position', 'right') === 'left');
@endphp

<section class="section-shell">
    <div class="grid items-center gap-8 lg:grid-cols-2">
        <div class="{{ $reverse ? 'lg:order-2' : '' }}">
            <div class="section-title">
                <h2>{{ $getText('title', $section->title ?? '') }}</h2>
                <p>{{ $getText('subtitle', $section->subtitle ?? '') }}</p>
            </div>
            <div class="rich-content mt-4 max-w-none text-sm">
                {!! $getText('body') !!}
            </div>
            @if (! empty($getValue('button_url')))
                <div class="mt-5">
                    <x-button href="{{ $getValue('button_url') }}" variant="primary" size="md">
                        {{ $getText('button_text', __('web.home.cta')) }}
                    </x-button>
                </div>
            @endif
        </div>
        @if ($image)
            <div class="{{ $reverse ? 'lg:order-1' : '' }}">
                <div class="overflow-hidden rounded-3xl border border-default bg-surface">
                    <img src="{{ $image }}" alt="{{ $getText('title', $section->title ?? '') }}" class="h-64 w-full object-cover sm:h-80">
                </div>
            </div>
        @endif
    </div>
</section>







