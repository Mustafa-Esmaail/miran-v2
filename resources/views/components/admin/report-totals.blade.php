@props([
    /** @var array<int, array{label: string, value: string|int|float|\Illuminate\Contracts\Support\Htmlable|null, hint?: string|null}> */
    'items' => [],
])

{{-- One continuous KPI strip under (or above) a report's table. --}}
<div {{ $attributes->class(['fi-report-totals grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:grid-cols-3 lg:grid-flow-col lg:auto-cols-fr lg:grid-cols-none dark:border-gray-700 dark:bg-gray-900']) }}>
    @foreach ($items as $item)
        <div class="fi-report-totals-item flex flex-col gap-1 border-gray-200 px-4 py-3 not-first:border-s dark:border-gray-700">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
            <span class="fi-report-totals-value text-lg font-semibold tabular-nums text-gray-950 dark:text-white">
                {{ $item['value'] ?? '—' }}
            </span>
            @if (filled($item['hint'] ?? null))
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['hint'] }}</span>
            @endif
        </div>
    @endforeach
</div>
