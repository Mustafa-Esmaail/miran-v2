@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $cards = $getValue('cards', []);
    $cards = is_array($cards) ? $cards : [];
    $image = $getValue('image', null);
    $image = $image ? (str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image)) : null;
    $statValue = $getValue('stat_value', '200+');
    $statLabel = $getText('stat_label', __('web.home.how_it_works_stat_label'));
    $skillLabel = $getText('skill_label', __('web.home.how_it_works_skill_label'));
    $skillPercent = (int) ($getValue('skill_percent', 100));
    $ctaUrl = $getValue('cta_url', '/about');
@endphp

<section class="section-shell">
    <div class="mx-auto w-full max-w-[1304px] rounded-3xl bg-white px-6 py-6 sm:px-10 lg:px-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <h2 class="text-3xl font-bold text-[#322f37] sm:text-[42px]">
                {{ $getText('title', $section->title ?? __('web.home.how_it_works_title')) }}
            </h2>
            <p class="max-w-3xl text-sm font-medium text-[#7e7e7e]">
                {{ $getText('subtitle', $section->subtitle ?? __('web.home.how_it_works_subtitle')) }}
            </p>
        </div>


        <div class="mt-14 grid items-center gap-12 lg:grid-cols-2">
            <div class="relative">
                <div class="absolute left-6 top-16 hidden h-[303px] w-[378px] rounded-tr-[50px] bg-[#ff8057] lg:block"></div>
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $getText('feature_title', $section->title ?? '') }}"
                        class="relative z-10 mx-auto h-[360px] w-full max-w-[420px] rounded-2xl object-cover sm:h-[420px] lg:h-[450px]">
                @endif

                <div class="absolute bottom-6 left-4 z-20 hidden items-center gap-4 rounded-lg bg-white p-5 shadow-[24px_24px_70px_rgba(0,0,0,0.25)] lg:flex">
                    <div class="flex h-[47px] w-[47px] items-center justify-center rounded-md bg-[#5bb6d2]">
                        <svg width="24" height="24" viewBox="0 0 47 47" fill="none" class="h-6 w-6">
                            <path
                                d="M17.012 30H32V16C32 15.4696 31.7893 14.9609 31.4142 14.5858C31.0391 14.2107 30.5304 14 30 14H17C15.794 14 14 14.799 14 17V31C14 33.201 15.794 34 17 34H32V32H17.012C16.55 31.988 16 31.805 16 31C16 30.195 16.55 30.012 17.012 30ZM19 18H28V20H19V18Z"
                                fill="white" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-[#151318]">{{ $statValue }}</p>
                        <p class="text-base font-medium text-[#7b7584]">{{ $statLabel }}</p>
                    </div>
                </div>

                <div class="absolute right-2 top-10 z-20 hidden flex-col gap-3 rounded-lg bg-white p-5 shadow-[24px_24px_70px_rgba(0,0,0,0.25)] lg:flex">
                    <p class="text-base font-medium text-[#7b7584]">{{ $skillLabel }}</p>
                    <div class="h-2 w-[189px] rounded-[200px] bg-[#f1e7df]">
                        <div class="h-2 rounded-[200px] bg-[#99745c]" style="width: {{ $skillPercent }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <h3 class="text-[32px] font-bold text-[#322f37] sm:text-[42px]">
                    {{ $getText('feature_title', __('web.home.how_it_works_feature_title')) }}
                </h3>
                <p class="text-sm font-medium text-[#7e7e7e]">
                    {{ $getText('feature_body', __('web.home.how_it_works_feature_body')) }}
                </p>
                <a href="{{ $ctaUrl }}" class="inline-flex items-center gap-2.5 rounded-[900px] bg-white px-5 py-2 shadow-sm">
                    <span class="text-base font-bold text-[#374746]">
                        {{ $getText('cta_text', __('web.home.how_it_works_cta_text')) }}
                    </span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                        <path
                            d="M10.707 17.707L16.414 12L10.707 6.29297L9.29303 7.70697L13.586 12L9.29303 16.293L10.707 17.707Z"
                            fill="#6D6775"></path>
                    </svg>
                </a>
            </div>
        </div>
          <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                @php
                    $icon = $card['icon'] ?? 'discover';
                @endphp
                <div class="rounded-xl bg-white p-6 shadow-[40px_0px_90px_rgba(55,71,70,0.14)]">
                    <div class="flex h-[66px] w-[66px] items-center justify-center rounded-xl bg-gradient-to-b from-[#374746] to-[#86ADAB]">
                        @if ($icon === 'access')
                            <svg width="40" height="40" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8">
                                <path
                                    d="M33 32.3735C29.32 32.3735 26.3333 35.3602 26.3333 39.0402C26.3333 40.0269 26.5466 40.9602 26.9466 41.7869C27.0533 42.0269 27.1733 42.2535 27.3066 42.4669C28.4533 44.4002 30.5733 45.7069 33 45.7069C35.4266 45.7069 37.5466 44.4002 38.6933 42.4669C38.8266 42.2535 38.9466 42.0269 39.0533 41.7869C39.4533 40.9602 39.6666 40.0269 39.6666 39.0402C39.6666 35.3602 36.68 32.3735 33 32.3735ZM35.76 38.4669L32.92 41.0935C32.7333 41.2669 32.48 41.3602 32.24 41.3602C31.9866 41.3602 31.7333 41.2669 31.5333 41.0669L30.2133 39.7469C29.8266 39.3602 29.8266 38.7202 30.2133 38.3335C30.6 37.9469 31.24 37.9469 31.6266 38.3335L32.2666 38.9735L34.4 37.0002C34.8133 36.6269 35.44 36.6535 35.8133 37.0535C36.1866 37.4535 36.16 38.0802 35.76 38.4669Z"
                                    fill="white" />
                                <path
                                    d="M43.9333 39.7599C43.4533 40.1865 42.9333 40.5465 42.3733 40.8399C41.8933 41.0799 41.4133 40.6132 41.4933 40.0799C41.5466 39.6932 41.5733 39.2932 41.5733 38.8799C41.5733 34.1065 37.68 30.2132 32.9066 30.2132C28.1333 30.2132 24.24 34.1065 24.24 38.8799C24.24 39.4932 24.3066 40.0799 24.4266 40.6532C24.52 41.1199 24.16 41.5865 23.6933 41.4665C18.4266 40.1865 18.4 32.0799 24.2933 31.6665H24.36C20.08 19.7599 38.1333 14.9999 40.2 27.5065C45.9733 28.2399 48.3066 35.9332 43.9333 39.7599Z"
                                    fill="white" />
                            </svg>
                        @elseif ($icon === 'share')
                            <svg width="40" height="40" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8">
                                <path
                                    d="M34.3334 26.2189V20.4482L40.6094 26.7242L45.6414 31.7562L40.52 36.0242L34.3334 41.1802V35.5336C23.472 34.7149 19.6667 41.0002 19.6667 41.0002C19.6667 37.0842 19.9894 33.0202 23.068 29.9429C26.6467 26.3629 31.8294 26.1096 34.3334 26.2189Z"
                                    fill="white" />
                            </svg>
                        @else
                            <svg width="40" height="40" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8">
                                <path
                                    d="M42.364 39.6358C41.7395 39.0377 41.1317 38.4223 40.5413 37.7905C40.0453 37.2865 39.7467 36.9198 39.7467 36.9198L36.0133 35.1372C37.508 33.442 38.3329 31.2598 38.3333 28.9998C38.3333 23.8545 34.1467 19.6665 29 19.6665C23.8533 19.6665 19.6667 23.8545 19.6667 28.9998C19.6667 34.1452 23.8533 38.3332 29 38.3332C31.3507 38.3332 33.4933 37.4532 35.1373 36.0145L36.92 39.7478C36.92 39.7478 37.2867 40.0465 37.7907 40.5425C38.3067 41.0265 38.9853 41.6812 39.636 42.3652L41.4467 44.2212L42.252 45.0825L45.08 42.2545L44.2187 41.4492C43.7133 40.9532 43.0387 40.2945 42.364 39.6358ZM29 35.6665C25.324 35.6665 22.3333 32.6758 22.3333 28.9998C22.3333 25.3238 25.324 22.3332 29 22.3332C32.676 22.3332 35.6667 25.3238 35.6667 28.9998C35.6667 32.6758 32.676 35.6665 29 35.6665Z"
                                    fill="white" />
                            </svg>
                        @endif
                    </div>
                    <div class="mt-6 space-y-3">
                        <p class="text-xl font-bold text-[#322f37]">
                            {{ $card["title_{$locale}"] ?? $card['title_en'] ?? $card['title_ar'] ?? '' }}
                        </p>
                        <p class="text-xs text-[#7e7e7e]">
                            {{ $card["body_{$locale}"] ?? $card['body_en'] ?? $card['body_ar'] ?? '' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>






