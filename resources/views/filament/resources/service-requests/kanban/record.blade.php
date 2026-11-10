<?php /** @var \App\Models\ServiceRequest $record */ ?>
@php
    $status = $record->statusModel;
    $borderColor = $status?->stage?->color ?? $status?->color ?? '#6b7280';
    $badgeColor = $status?->color ?? '#6b7280';
    $editUrl = $editUrlResolver($record);
    $isFollowUpOverdue = $this->isFollowUpOverdue($record);
@endphp

<div
    id="{{ $record->getKey() }}"
    class="record cursor-grab rounded-lg border-s-4 border border-gray-200 bg-white px-4 py-3 dark:border-gray-600 dark:bg-gray-700"
    style="border-inline-start-color: {{ $borderColor }}"
>
    <div class="flex items-center justify-between gap-2">
        <div class="min-w-0">
            <a href="{{ $editUrl }}" class="block text-sm font-semibold text-gray-800 dark:text-gray-100 hover:underline">
                {{ $record->phone }}
            </a>

            @if (filled($record->name))
                <p class="truncate text-xs text-gray-600 dark:text-gray-300">{{ $record->name }}</p>
            @endif
        </div>

        <button
            type="button"
            wire:click="mountAction('pickStatus', { recordId: {{ $record->getKey() }} })"
            class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
            title="{{ __('admin.actions.choose_status') }}"
        >
            <x-heroicon-o-pencil-square class="h-4 w-4" />
        </button>
    </div>

    <div class="mt-2 flex flex-wrap items-center gap-1">
        <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium text-white"
            style="background-color: {{ $badgeColor }}"
        >
            {{ $status?->getTranslatedName() ?? $record->status }}
        </span>

        @if ($isFollowUpOverdue)
            <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle" size="sm" data-follow-up-overdue>
                {{ __('admin.kanban.follow_up_overdue') }}
            </x-filament::badge>
        @endif
    </div>

    <div class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
        <p>{{ __('admin.labels.assigned_to') }}: {{ $record->assignedTo?->name ?? '—' }}</p>
        <p>{{ __('admin.labels.created_at') }}: {{ $record->created_at?->format('Y-m-d H:i') }}</p>
        <p>{{ __('admin.labels.last_activity_at') }}: {{ $record->last_activity_at?->format('Y-m-d H:i') ?? '—' }}</p>
        <p @class(['font-semibold text-danger-600 dark:text-danger-400' => $isFollowUpOverdue])>{{ __('admin.labels.status_date') }}: {{ $record->status_date?->format('Y-m-d H:i') ?? '—' }}</p>
    </div>
</div>
