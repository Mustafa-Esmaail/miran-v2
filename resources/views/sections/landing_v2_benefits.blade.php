@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $items = function (string $key) use ($section, $locale) {
        $value = $section->fieldValue($key, []);

        return collect(is_array($value) ? $value : [])
            ->map(fn (array $item) => [
                'text' => LandingV2::itemText($item, 'text', $locale),
                'note' => LandingV2::itemText($item, 'note', $locale),
            ])
            ->filter(fn (array $item) => $item['text'] !== '');
    };

    $columns = [
        ['class' => 'gen', 'title' => $section->fieldText('during_title', $locale), 'items' => $items('during_items')],
        ['class' => 'stay', 'title' => $section->fieldText('after_title', $locale), 'items' => $items('after_items')],
    ];

    $stats = $section->fieldValue('stats', []);
    $stats = collect(is_array($stats) ? $stats : [])
        ->map(fn (array $stat) => [
            'value' => (string) ($stat['value'] ?? ''),
            'label' => LandingV2::itemText($stat, 'label', $locale),
        ])
        ->filter(fn (array $stat) => $stat['value'] !== '');

    $title = $section->fieldText('title', $locale);
    $ctaText = $section->fieldText('cta_text', $locale, __('web.landing_v2.cta'));
@endphp

<section class="why">
    <div class="wrap">
        @if ($title !== '')
            <h2>{{ LandingV2::highlight($title) }}</h2>
        @endif

        <div class="grid2">
            @foreach ($columns as $column)
                <div class="bcol {{ $column['class'] }}">
                    @if ($column['title'] !== '')
                        <h3>{{ $column['title'] }}</h3>
                    @endif
                    <ul>
                        @foreach ($column['items'] as $item)
                            <li>
                                <span>
                                    {{ LandingV2::highlight($item['text']) }}
                                    @if ($item['note'] !== '')
                                        <small>{{ $item['note'] }}</small>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

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
