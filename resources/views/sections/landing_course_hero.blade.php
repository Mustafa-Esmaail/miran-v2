@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $markUrl = LandingV2::imageUrl($section->fieldValue('mark_image')) ?? asset('img/landing-v2/mark.webp');
    $logoUrl = LandingV2::imageUrl($section->fieldValue('logo')) ?? asset('img/landing-v2/miran-light.svg');
    $heading = $section->fieldText('heading', $locale, isset($landable) ? (string) $landable->getTranslation('title', $locale) : '');
    $subheading = $section->fieldText('subheading', $locale);
    $price = $section->fieldText('price', $locale);
    $oldPrice = $section->fieldText('old_price', $locale);
    $showCurrencyIcon = (bool) $section->fieldValue('show_currency_icon', true);
    $installment = $section->fieldText('installment', $locale);
    $installmentBadge = $section->fieldText('installment_badge', $locale);
    $formTitle = $section->fieldText('form_title', $locale, __('web.landing_v2.form_title'));
    $showNextBatch = (bool) $section->fieldValue('show_next_batch', true);

    $badges = $section->fieldValue('badges', []);
    $badges = collect(is_array($badges) ? $badges : [])
        ->map(fn (array $badge) => [
            'image' => LandingV2::imageUrl($badge['image'] ?? null),
            'name' => LandingV2::itemText($badge, 'name', $locale),
            'detail' => LandingV2::itemText($badge, 'detail', $locale),
        ])
        ->filter(fn (array $badge) => $badge['name'] !== '');

    $lockedCourseId = (isset($landable) && $landable instanceof \App\Models\Course) ? $landable->id : null;
    $lockedBundleId = (isset($landable) && $landable instanceof \App\Models\Bundle) ? $landable->id : null;

    $nextBatch = __('web.landing_course.next_batch_soon');
    $nextGroupStart = ($showNextBatch && $lockedCourseId) ? $landable->nextGroup()?->start_at : null;
    if ($nextGroupStart) {
        $nextBatch = $nextGroupStart->isToday()
            ? __('web.landing_v2.next_batch_today')
            : $nextGroupStart->locale($locale)->translatedFormat('l j F');
    }
@endphp

<section class="hero hero-course" id="top">
    <img class="mark" src="{{ $markUrl }}" alt="" width="360" height="388" fetchpriority="high" decoding="async">
    <div class="wrap">
        @if ($badges->isNotEmpty())
            <div class="trust">
                @foreach ($badges as $badge)
                    <div class="badge">
                        @if ($badge['image'])
                            <span class="lg"><img src="{{ $badge['image'] }}" alt="{{ $badge['name'] }}" width="24" height="24"></span>
                        @endif
                        <div>
                            <b>{{ $badge['name'] }}</b>
                            @if ($badge['detail'] !== '')
                                <span>{{ $badge['detail'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="hrow">
            @if ($heading !== '')
                <h1>{{ LandingV2::highlight($heading) }}</h1>
            @endif
            <img class="mlogo" src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="42" height="55" decoding="async">
        </div>

        @if ($subheading !== '')
            <p class="lead">{{ $subheading }}</p>
        @endif

        <div class="formcard" id="form">
            @if ($showNextBatch)
                <div class="next">
                    <span class="dot"></span>
                    <span>{{ __('web.landing_v2.next_batch') }} <b>{{ $nextBatch }}</b></span>
                </div>
            @endif

            @if ($price !== '')
                <div class="pline">
                    <b>{{ $price }}@if ($showCurrencyIcon) @include('sections.partials.sar-icon')@endif</b>
                    @if ($oldPrice !== '')
                        <s>{{ $oldPrice }}</s>
                    @endif
                </div>
            @endif

            @if ($installment !== '')
                <div class="pay">
                    {{ LandingV2::highlight($installment) }}
                    @if ($installmentBadge !== '')
                        <span class="tamara">{{ $installmentBadge }}</span>
                    @endif
                </div>
            @endif

            <h3>{{ $formTitle }}</h3>

            <livewire:landing.lead-form-v2
                :section-id="$section->id"
                :locked-course-id="$lockedCourseId"
                :locked-bundle-id="$lockedBundleId"
                :landing-slug="$section->page?->slug"
                :page-id="$section->page_id"
            />
        </div>
    </div>
</section>
