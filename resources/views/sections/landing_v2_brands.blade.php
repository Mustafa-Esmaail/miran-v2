@php
    use App\Support\LandingV2;

    $locale = app()->getLocale();
    $logoUrl = LandingV2::imageUrl($section->fieldValue('logo')) ?? asset('img/landing-v2/miran.svg');
    $brands = $section->fieldValue('brands', []);
    $brands = collect(is_array($brands) ? $brands : [])
        ->map(fn (array $brand) => [
            'url' => LandingV2::imageUrl($brand['image'] ?? null),
            'alt' => (string) ($brand['alt'] ?? ''),
            'caption' => LandingV2::itemText($brand, 'caption', $locale),
        ])
        ->filter(fn (array $brand) => filled($brand['url']))
        ->values();

    // The center logo sits in the middle of the accreditation logos.
    [$before, $after] = $brands->partition(fn ($brand, int $index) => $index < (int) ceil($brands->count() / 2));
@endphp

<div class="brandrow">
    <div class="wrap">
        @foreach ($before as $brand)
            <div class="brand">
                <img src="{{ $brand['url'] }}" alt="{{ $brand['alt'] }}" loading="eager">
                @if ($brand['caption'] !== '')
                    <small>{{ $brand['caption'] }}</small>
                @endif
            </div>
        @endforeach

        <div class="brand miran">
            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}">
        </div>

        @foreach ($after as $brand)
            <div class="brand">
                <img src="{{ $brand['url'] }}" alt="{{ $brand['alt'] }}" loading="eager">
                @if ($brand['caption'] !== '')
                    <small>{{ $brand['caption'] }}</small>
                @endif
            </div>
        @endforeach
    </div>
</div>
