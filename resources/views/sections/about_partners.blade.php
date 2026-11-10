@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $logos = $getValue('logos', []);
    $logos = is_array($logos) ? $logos : [];
    $logos = collect($logos)->map(function ($logo) {
        return str_starts_with($logo, 'http') ? $logo : \App\Support\S3Url::temporary($logo);
    })->filter()->values()->all();
    $fallback = [
        'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
        'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
        'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
    ];
    $renderLogos = $logos !== [] ? $logos : $fallback;
@endphp

<section class="border-y border-[#f1f1f1] bg-white py-6">
    <div class="app-container">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-primary sm:text-3xl">
                {{ $getText('title', $section->title ?? '') }}
            </h2>
        </div>
        <div class="mt-10 grid grid-cols-2 items-center gap-6 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($renderLogos as $logo)
                <img src="{{ $logo }}" alt="{{ __('web.sections.partners.logo_alt') }}" class="partner-logo mx-auto h-12">
            @endforeach
        </div>
    </div>
</section>







