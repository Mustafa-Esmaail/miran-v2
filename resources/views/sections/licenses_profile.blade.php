@php
    $locale = app()->getLocale();
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section class="py-14">
    <div class="app-container">

        @if ($section->title)
            <div class="mb-10 text-center">
                <h2 class="text-2xl font-bold text-brand-dark sm:text-3xl">{{ $section->title }}</h2>
                @if ($section->subtitle)
                    <p class="mt-2 text-sm text-secondary">{{ $section->subtitle }}</p>
                @endif
            </div>
        @endif

        <div class="mx-auto max-w-3xl space-y-3">
            @foreach ($items as $index => $item)
                @php
                    $title = $item["title_{$locale}"] ?? ($item['title_ar'] ?? ($item['title_en'] ?? ''));
                    $body = $item["body_{$locale}"] ?? ($item['body_ar'] ?? ($item['body_en'] ?? ''));
                    $image = $item['image'] ?? null;
                @endphp

                <div x-data="{ open: false }"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                    <button @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 px-6 py-4 text-start"
                        :aria-expanded="open">
                        <span class="flex items-center gap-3 text-base font-semibold text-brand-dark">
                            <span class="text-xl leading-none">📑</span>
                            {{ $title }}
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-brand-accent transition-transform duration-300"
                            :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" x-collapse class="border-t border-gray-100">
                        <div class="px-6 py-5 space-y-4">
                            @if ($body)
                                <div
                                    class="prose prose-sm max-w-none text-secondary [&_a]:text-brand-accent [&_a]:underline">
                                    {!! $body !!}
                                </div>
                            @endif
                            @if ($image)
                                <img src="{{ str_starts_with($image, 'http') ? $image : \App\Support\S3Url::temporary($image) }}"
                                    alt="{{ $title }}"
                                    class="mx-auto max-w-full rounded-xl border border-gray-100 shadow-sm" />
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
