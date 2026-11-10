@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $markUrl = LandingV2::imageUrl($section->fieldValue('mark_image')) ?? asset('img/landing-v2/mark.webp');
    $heading = $section->fieldText('heading', $locale, isset($landable) ? (string) $landable->getTranslation('title', $locale) : '');
    $subheading = $section->fieldText('subheading', $locale);
    $price = $section->fieldText('price', $locale);
    $formTitle = $section->fieldText('form_title', $locale, __('web.landing_v2.form_title'));
    $showNextBatch = (bool) $section->fieldValue('show_next_batch', true);
    $facts = $section->fieldValue('facts', []);
    $facts = collect(is_array($facts) ? $facts : [])
        ->map(fn (array $fact) => [
            'value' => LandingV2::itemText($fact, 'value', $locale),
            'label' => LandingV2::itemText($fact, 'label', $locale),
        ])
        ->filter(fn (array $fact) => $fact['value'] !== '');

    $lockedCourseId = (isset($landable) && $landable instanceof \App\Models\Course) ? $landable->id : null;
    $lockedBundleId = (isset($landable) && $landable instanceof \App\Models\Bundle) ? $landable->id : null;
@endphp

<section class="hero" id="top">
    <img class="mark" src="{{ $markUrl }}" alt="">
    <div class="wrap">
        @if ($heading !== '')
            <h1>{{ LandingV2::highlight($heading) }}</h1>
        @endif

        @if ($subheading !== '')
            <p class="lead">{{ $subheading }}</p>
        @endif

        @if ($facts->isNotEmpty())
            <div class="facts">
                @foreach ($facts as $fact)
                    <div class="fact"><b>{{ $fact['value'] }}</b><span>{{ $fact['label'] }}</span></div>
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
                <div class="pline"><b>{{ $price }}</b></div>
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
