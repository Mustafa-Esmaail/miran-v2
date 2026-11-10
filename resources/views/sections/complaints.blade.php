@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $tag = $getText('tag', __('web.home.complaints_tag'));
    $title = $getText('title', $section->title ?? __('web.home.complaints_title'));
    $subtitle = $getText('subtitle', $section->subtitle ?? __('web.home.complaints_subtitle'));
    $buttonText = $getText('button_text', __('web.home.complaints_cta'));
    $buttonUrl = $getValue('button_url', 'mailto:info@example.com');
@endphp

<section id="complaints" class="section-shell">
    <div class="card grid gap-6 border border-default bg-muted p-6 sm:p-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div>
            <p class="badge badge-ghost">{{ $tag }}</p>
            <h2 class="mt-3 text-2xl font-semibold text-primary">{{ $title }}</h2>
            <p class="mt-2 text-sm text-secondary">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-button href="{{ $buttonUrl }}" variant="primary" size="lg">{{ $buttonText }}</x-button>
            <x-button href="#contact" variant="ghost" size="lg">{{ __('web.nav.contact_request') }}</x-button>
        </div>
    </div>
</section>





