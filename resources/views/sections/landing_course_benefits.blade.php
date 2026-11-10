@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $list = function (string $key) use ($section) {
        $value = $section->fieldValue($key, []);

        return collect(is_array($value) ? $value : []);
    };

    $facts = $list('facts')
        ->map(fn (array $fact) => [
            'value' => LandingV2::itemText($fact, 'value', $locale),
            'label' => LandingV2::itemText($fact, 'label', $locale),
        ])
        ->filter(fn (array $fact) => $fact['value'] !== '');

    $iconItems = fn (string $key) => $list($key)
        ->map(fn (array $item) => [
            'image' => LandingV2::imageUrl($item['image'] ?? null),
            'text' => LandingV2::itemText($item, 'text', $locale),
        ])
        ->filter(fn (array $item) => $item['text'] !== '')
        ->values();

    $tools = $iconItems('tools');
    $extras = $iconItems('extras');

    $stories = $list('stories')
        ->map(fn (array $story) => [
            'title' => LandingV2::itemText($story, 'title', $locale),
            'summary' => LandingV2::itemText($story, 'summary', $locale),
            'now' => LandingV2::itemText($story, 'now', $locale),
            'after' => LandingV2::itemText($story, 'after', $locale),
        ])
        ->filter(fn (array $story) => $story['title'] !== '')
        ->values();

    $stats = $list('stats')
        ->map(fn (array $stat) => [
            'value' => (string) ($stat['value'] ?? ''),
            'label' => LandingV2::itemText($stat, 'label', $locale),
        ])
        ->filter(fn (array $stat) => $stat['value'] !== '');

    $title = $section->fieldText('title', $locale);
    $extrasTitle = $section->fieldText('extras_title', $locale);
    $storiesTitle = $section->fieldText('stories_title', $locale);
    $storiesSubtitle = $section->fieldText('stories_subtitle', $locale);
    $ctaText = $section->fieldText('cta_text', $locale, __('web.landing_v2.cta'));
@endphp

@if ($facts->isNotEmpty())
    <section class="clients course-facts">
        <div class="wrap">
            <div class="facts">
                @foreach ($facts as $fact)
                    <div class="fact"><b>{{ $fact['value'] }}</b><span>{{ $fact['label'] }}</span></div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="why why-course" id="benefits">
    <div class="wrap">
        @if ($title !== '')
            <h2>{{ LandingV2::highlight($title) }}</h2>
        @endif

        @if ($tools->isNotEmpty())
            <div class="kit" style="--kit-cols: {{ $tools->count() }}">
                @foreach ($tools as $tool)
                    {{-- An odd last card spans the full row. --}}
                    <div @class(['kt', 'wide' => $loop->last && $loop->count % 2 === 1])>
                        @if ($tool['image'])
                            <img src="{{ $tool['image'] }}" alt="" width="58" height="58" loading="lazy" decoding="async">
                        @endif
                        <b>{{ $tool['text'] }}</b>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($extras->isNotEmpty())
            <div class="extras">
                @if ($extrasTitle !== '')
                    <p>{{ $extrasTitle }}</p>
                @endif
                <div class="exg" style="--cols: {{ $extras->count() }}">
                    @foreach ($extras as $extra)
                        <div class="ex">
                            @if ($extra['image'])
                                <img src="{{ $extra['image'] }}" alt="" width="44" height="44" loading="lazy" decoding="async">
                            @endif
                            <span>{{ $extra['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($stories->isNotEmpty())
            <details class="days" id="outcomes">
                <summary>
                    <span class="sq">
                        <b>{{ $storiesTitle }}</b>
                        @if ($storiesSubtitle !== '')
                            <small>{{ $storiesSubtitle }}</small>
                        @endif
                    </span>
                </summary>
                <div class="story">
                    @foreach ($stories as $story)
                        <article class="st">
                            <span class="num">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <h4>{{ $story['title'] }}</h4>
                            @if ($story['summary'] !== '')
                                <p class="simp">{{ $story['summary'] }}</p>
                            @endif
                            @if ($story['now'] !== '' || $story['after'] !== '')
                                <div class="ba">
                                    @if ($story['now'] !== '')
                                        <div class="now"><i>{{ __('web.landing_course.story_now') }}</i><span>{{ $story['now'] }}</span></div>
                                    @endif
                                    @if ($story['now'] !== '' && $story['after'] !== '')
                                        <div class="arrow" aria-hidden="true">↓</div>
                                    @endif
                                    @if ($story['after'] !== '')
                                        <div class="aft"><i>{{ __('web.landing_course.story_after') }}</i><span>{{ $story['after'] }}</span></div>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </details>
        @endif

        @if ($stats->isNotEmpty())
            <div class="nv2">
                @foreach ($stats as $stat)
                    <div><b>{{ $stat['value'] }}</b><span>{{ $stat['label'] }}</span></div>
                @endforeach
            </div>
        @endif

        <a class="btn cta-end" href="#form" data-lv2-cta>{{ $ctaText }}</a>
    </div>
</section>

@once('landing-v2-cta')
    <script>
        document.addEventListener('click', (event) => {
            const cta = event.target.closest('[data-lv2-cta]');
            const form = document.getElementById('form');
            if (!cta || !form) return;

            event.preventDefault();
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => form.querySelector('input[type="tel"]')?.focus({ preventScroll: true }), 500);
        });
    </script>
@endonce
