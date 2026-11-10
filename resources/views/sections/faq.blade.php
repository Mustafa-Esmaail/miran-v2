@php
    $locale = app()->getLocale();
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section class="section-shell">
    <div class="section-title">
        <h2>{{ $section->title ?? '' }}</h2>
        <p>{{ $section->subtitle ?? '' }}</p>
    </div>
    <div class="mt-6 space-y-4">
        @foreach ($items as $item)
            @php
                $question = $item["question_{$locale}"] ?? $item['question_en'] ?? $item['question_ar'] ?? '';
                $answer = $item["answer_{$locale}"] ?? $item['answer_en'] ?? $item['answer_ar'] ?? '';
            @endphp
            <div class="card p-5">
                <div class="text-sm font-semibold text-primary">{{ $question }}</div>
                <div class="mt-2 text-sm text-secondary">{{ $answer }}</div>
            </div>
        @endforeach
    </div>
</section>





