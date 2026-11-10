@php
    $isComplete = abs($activeWeightTotal - 100) < 0.001;
@endphp

<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
    <span class="text-sm text-gray-600 dark:text-gray-300">{{ __('admin.call_quality.criteria.weight_total') }}</span>
    <span
        dir="ltr"
        @class([
            'inline-flex items-center rounded-md px-2 py-0.5 text-sm font-semibold tabular-nums',
            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $isComplete,
            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => ! $isComplete,
        ])
    >
        {{ \App\Filament\Admin\Resources\CallQualityCriteria\Tables\CallQualityCriteriaTable::formatWeight($activeWeightTotal) }}%
    </span>
    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.call_quality.criteria.weight_hint') }}</span>
</div>
