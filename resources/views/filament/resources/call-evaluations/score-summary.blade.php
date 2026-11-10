@php
    $percent = $summary['percent'] ?? null;
    $level = $summary['level'] ?? null;
    $scored = (int) ($summary['scored'] ?? 0);
    $criteriaCount = (int) ($criteriaCount ?? 0);
@endphp

<div class="flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
    <div class="flex flex-col gap-1">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
            {{ __('admin.call_quality.fields.total') }}
        </span>
        <span class="text-4xl font-bold leading-none text-gray-950 dark:text-white" dir="ltr">
            {{ $percent === null ? '—' : number_format((float) $percent, 1).'%' }}
        </span>
    </div>

    <div class="flex flex-col gap-2">
        <span class="text-sm text-gray-700 dark:text-gray-200">
            {{ __('admin.call_quality.helpers.points', ['points' => $summary['points'] ?? 0, 'max' => $summary['max_points'] ?? 0]) }}
        </span>

        <div class="flex flex-wrap items-center gap-2">
            <x-filament::badge :color="\App\Support\CallQuality\CallQualityLevels::color($level)">
                {{ \App\Support\CallQuality\CallQualityLevels::label($level) }}
            </x-filament::badge>

            @if ($criteriaCount > 0 && $scored < $criteriaCount)
                <span class="text-xs text-warning-600 dark:text-warning-400">
                    {{ __('admin.call_quality.helpers.scored_of', ['scored' => $scored, 'total' => $criteriaCount]) }}
                </span>
            @endif
        </div>
    </div>
</div>
