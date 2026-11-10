@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $tag = $getText('tag', __('web.home.corporate_tag'));
    $title = $getText('title', $section->title ?? __('web.home.corporate_title'));
    $subtitle = $getText('subtitle', $section->subtitle ?? __('web.home.corporate_subtitle'));
    $buttonText = $getText('button_text', __('web.nav.contact_request'));
    $buttonUrl = $getValue('button_url', '#contact');
@endphp

<section id="corporate" class="section-shell">
    <div class="card grid gap-6 bg-surface p-6 sm:p-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div>
            <p class="badge badge-primary">{{ $tag }}</p>
            <h2 class="mt-3 text-2xl font-semibold text-primary">{{ $title }}</h2>
            <p class="mt-2 text-sm text-secondary">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-button href="{{ $buttonUrl }}" variant="primary" size="lg">{{ $buttonText }}</x-button>
            <x-button href="{{ route('courses.index') }}" variant="secondary" size="lg">{{ __('web.home.view_courses') }}</x-button>
        </div>
    </div>
</section>





