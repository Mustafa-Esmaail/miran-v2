@php
    $locale = app()->getLocale();
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
    $getImage = function (?string $path): ?string {
        if (! $path) {
            return null;
        }
        return str_starts_with($path, 'http') ? $path : \App\Support\S3Url::temporary($path);
    };
@endphp

<section class="section-shell">
    <div class="section-title">
        <h2>{{ $section->title ?? '' }}</h2>
        <p>{{ $section->subtitle ?? '' }}</p>
    </div>
    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($items as $item)
            @php
                $title = $item["title_{$locale}"] ?? $item['title_en'] ?? $item['title_ar'] ?? '';
                $desc = $item["description_{$locale}"] ?? $item['description_en'] ?? $item['description_ar'] ?? '';
                $image = $getImage($item['image'] ?? null);
            @endphp
            <div class="card space-y-3 p-5">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $title }}" class="h-10 w-10 rounded-xl object-cover">
                @else
                    <span class="icon-chip">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2.5 5 5.5.8-4 3.9.9 5.6-4.9-2.7-4.9 2.7.9-5.6-4-3.9 5.5-.8L12 3z" />
                        </svg>
                    </span>
                @endif
                <div class="text-lg font-semibold text-primary">{{ $title }}</div>
                <p class="text-sm text-secondary">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>







