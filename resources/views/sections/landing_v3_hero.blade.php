@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $markUrl = LandingV2::imageUrl($section->fieldValue('mark_image')) ?? asset('img/landing-v2/mark.webp');
    $logoUrl = LandingV2::imageUrl($section->fieldValue('logo')) ?? asset('img/landing-v2/miran-light.svg');
    $heading = $section->fieldText('heading', $locale, isset($landable) ? (string) $landable->getTranslation('title', $locale) : '');
    $subheading = $section->fieldText('subheading', $locale);
    $price = $section->fieldText('price', $locale);
    $showCurrencyIcon = (bool) $section->fieldValue('show_currency_icon', true);
    $priceNote = $section->fieldText('price_note', $locale);
    $priceNoteImageUrl = LandingV2::imageUrl($section->fieldValue('price_note_image'));
    $formTitle = $section->fieldText('form_title', $locale, __('web.landing_v2.form_title'));
    $showNextBatch = (bool) $section->fieldValue('show_next_batch', true);

    $facts = $section->fieldValue('facts', []);
    $facts = collect(is_array($facts) ? $facts : [])
        ->map(fn (array $fact) => [
            'value' => LandingV2::itemText($fact, 'value', $locale),
            'label' => LandingV2::itemText($fact, 'label', $locale),
            'image' => LandingV2::imageUrl($fact['image'] ?? null),
        ])
        ->filter(fn (array $fact) => $fact['value'] !== '' || filled($fact['image']));

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
@endphp

<section class="hero hero-v3" id="top">
    <img class="mark" src="{{ $markUrl }}" alt="" width="360" height="388" fetchpriority="high" decoding="async">
    <div class="wrap">
        <div class="hrow">
            @if ($heading !== '')
                <h1>{{ LandingV2::highlight($heading) }}</h1>
            @endif
            <img class="mlogo" src="{{ $logoUrl }}" alt="{{ config('app.name') }}" width="33" height="44" decoding="async">
        </div>

        @if ($subheading !== '')
            <p class="lead">{{ $subheading }}</p>
        @endif

        @if ($facts->isNotEmpty())
            <div class="facts">
                @foreach ($facts as $fact)
                    @if ($fact['image'])
                        <div class="fact flogo">
                            <img class="plogo" src="{{ $fact['image'] }}" alt="{{ $fact['value'] }}">
                            <span>{{ $fact['label'] }}</span>
                        </div>
                    @else
                        <div class="fact"><b>{{ $fact['value'] }}</b><span>{{ $fact['label'] }}</span></div>
                    @endif
                @endforeach
            </div>
        @endif

        @if ($badges->isNotEmpty())
            <div class="trust">
                @foreach ($badges as $badge)
                    <div class="badge">
                        @if ($badge['image'])
                            <img src="{{ $badge['image'] }}" alt="">
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

        <div class="formcard" id="form">
            @if ($showNextBatch)
                <div class="next">
                    <span class="dot"></span>
                    <span>{{ __('web.landing_v2.next_batch') }} <b data-lv2-next-batch>{{ __('web.landing_v2.next_batch_next_week') }}</b></span>
                </div>
            @endif

            @if ($price !== '')
                <div class="pline">
                    <b>{{ $price }}@if ($showCurrencyIcon) @include('sections.partials.sar-icon')@endif</b>
                    @if ($priceNote !== '' || $priceNoteImageUrl)
                        <span class="ref">
                            {{ $priceNote }}
                            @if ($priceNoteImageUrl)
                                <img src="{{ $priceNoteImageUrl }}" alt="">
                            @endif
                        </span>
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

@include('sections.partials.landing-next-batch-script')
