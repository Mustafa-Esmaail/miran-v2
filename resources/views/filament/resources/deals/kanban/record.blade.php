@php
    $stageValue = $record->stage?->value ?? (string) $record->stage;

    $stageClasses = match ($stageValue) {
        'prospecting' => 'border-slate-300 bg-slate-50/80 dark:border-slate-600 dark:bg-slate-900/40',
        'qualification' => 'border-sky-300 bg-sky-50/80 dark:border-sky-700 dark:bg-sky-900/30',
        'proposal' => 'border-indigo-300 bg-indigo-50/80 dark:border-indigo-700 dark:bg-indigo-900/30',
        'negotiation' => 'border-amber-300 bg-amber-50/80 dark:border-amber-700 dark:bg-amber-900/30',
        'closed_won' => 'border-emerald-300 bg-emerald-50/80 dark:border-emerald-700 dark:bg-emerald-900/30',
        'closed_lost' => 'border-rose-300 bg-rose-50/80 dark:border-rose-700 dark:bg-rose-900/30',
        default => 'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-700',
    };
@endphp

<div id="{{ $record->getKey() }}" class="record rounded-lg px-4 py-3 cursor-grab border {{ $stageClasses }}">
    <a href="{{ route('filament.admin.resources.deals.edit', $record->getKey()) }}" class="block">
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $record->title }}</p>
            <span class="inline-flex items-center rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800/80 dark:text-gray-200">
                {{ __('admin.options.'.$stageValue) }}
            </span>
        </div>
        <p class="mt-1 text-xs text-gray-500">{{ $record->company?->name ?? '-' }}</p>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>{{ __('admin.labels.assigned_to') }}: {{ $record->employee?->name ?? '-' }}</span>
            <span>#{{ $record->getKey() }}</span>
        </div>
        <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
            <span>{{ __('admin.labels.expected_close_date') }}: {{ $record->expected_close_date?->format('Y-m-d') ?? '-' }}</span>
            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $this->formatDealValue((int) ($record->value ?? 0)) }}</span>
        </div>
    </a>
</div>
