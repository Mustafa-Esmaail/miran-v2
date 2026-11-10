@php
    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $backgroundImage = $getValue('background_image', null);
    $backgroundImage = $backgroundImage
        ? (str_starts_with($backgroundImage, 'http')
            ? $backgroundImage
            : \App\Support\S3Url::temporary($backgroundImage))
        : null;
    $sectionStyle = $backgroundImage
        ? "background-image: url('{$backgroundImage}'); background-size: cover; background-position: center;"
        : null;
@endphp

<section class="relative flex min-h-[35vh] items-center justify-center overflow-hidden bg-neutral-50 full-bleed"
    @if ($sectionStyle) style="{{ $sectionStyle }}" @endif>
    <div class="pointer-events-none absolute inset-0 bg-[#99745c]/15"></div>

    <svg width="456" height="402" viewBox="0 0 456 402" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute right-[-220px] top-[-360px] hidden lg:block">
        <circle cx="388" cy="14" r="388" fill="url(#ctaGlowTop)" fill-opacity="0.3"></circle>
        <defs>
            <radialGradient id="ctaGlowTop" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse"
                gradientTransform="translate(388 14) rotate(90) scale(388)">
                <stop stop-color="#C0945B"></stop>
                <stop offset="0.53" stop-color="#C0945B" stop-opacity="0.23"></stop>
                <stop offset="0.75" stop-color="#C0945B" stop-opacity="0.06"></stop>
                <stop offset="1" stop-color="#C0945B" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
    </svg>

    <svg width="406" height="456" viewBox="0 0 406 456" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-[-260px] top-[140px] hidden lg:block">
        <circle cx="18" cy="388" r="388" fill="url(#ctaGlowBottom)" fill-opacity="0.3"></circle>
        <defs>
            <radialGradient id="ctaGlowBottom" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse"
                gradientTransform="translate(18 388) rotate(90) scale(388)">
                <stop stop-color="#C0945B"></stop>
                <stop offset="0.53" stop-color="#C0945B" stop-opacity="0.23"></stop>
                <stop offset="0.75" stop-color="#C0945B" stop-opacity="0.06"></stop>
                <stop offset="1" stop-color="#C0945B" stop-opacity="0"></stop>
            </radialGradient>
        </defs>
    </svg>

    <div
        class="relative mx-auto flex max-w-3xl flex-col items-center justify-center gap-10 px-4 py-6 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-[#242528] sm:text-4xl lg:text-[44px]">
            {{ $getText('title', $section->title ?? __('web.home.cta_banner_title')) }}
        </h2>
        <p class="max-w-4xl text-base font-medium text-[#242528] sm:text-lg">
            {{ $getText('subtitle', $section->subtitle ?? __('web.home.cta_banner_subtitle')) }}
        </p>
        <a href="{{ $getValue('button_url', '#') }}"
            class="inline-flex items-center gap-2 rounded-[32px] bg-[#99745c] px-6 py-3 text-base font-medium text-white sm:text-lg">
            {{ $getText('button_text', __('web.home.cta_banner_button')) }}
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path
                    d="M21.25 12c0 .19-.07.38-.22.53l-6.07 6.07a.75.75 0 01-1.06-1.06L19.44 12l-5.54-5.54a.75.75 0 011.06-1.06l6.07 6.07c.15.15.22.34.22.53Z"
                    fill="white" />
                <path d="M21.08 12c0 .41-.34.75-.75.75H3.5a.75.75 0 010-1.5h16.83c.41 0 .75.34.75.75Z" fill="white" />
            </svg>
        </a>
    </div>
</section>
