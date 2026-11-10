@php
    use App\Support\CrmEntityLink;

    $entityUrl = CrmEntityLink::editUrl($record->entity_type, $record->entity_id);
    $statusValue = $record->status?->value ?? (string) $record->status;

    $statusClasses = match ($statusValue) {
        'todo' => 'border-slate-300 bg-slate-50/80 dark:border-slate-600 dark:bg-slate-900/40',
        'in_progress' => 'border-amber-300 bg-amber-50/80 dark:border-amber-700 dark:bg-amber-900/30',
        'done' => 'border-emerald-300 bg-emerald-50/80 dark:border-emerald-700 dark:bg-emerald-900/30',
        default => 'border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-700',
    };
@endphp

<div id="{{ $record->getKey() }}" class="record rounded-lg px-4 py-3 cursor-grab border {{ $statusClasses }}">
    <a href="{{ route('filament.admin.resources.crm-tasks.edit', $record->getKey()) }}" class="block">
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $record->title }}</p>
            <span class="inline-flex items-center rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-800/80 dark:text-gray-200">
                {{ __('admin.options.'.$statusValue) }}
            </span>
        </div>
        <div class="mt-2 text-xs text-gray-500">
            <p>{{ __('admin.labels.assigned_to') }}: {{ $record->employee?->name ?? '-' }}</p>
            <p>{{ __('admin.labels.due_at') }}: {{ $record->due_at?->format('Y-m-d H:i') ?? '-' }}</p>
        </div>
        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>#{{ $record->getKey() }}</span>
            @if($entityUrl)
                <span class="text-primary-600">{{ __('admin.labels.related_to') }} #{{ $record->entity_id }}</span>
            @endif
        </div>
    </a>
</div>
