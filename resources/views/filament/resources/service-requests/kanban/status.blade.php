<?php /** @var array{id: string, title: string, color: string, limit: int|null, records: array} $status */ ?>
@props(['status'])

<div class="mb-5 flex min-h-[18rem] flex-col">
    <h3 class="mb-2 px-2 font-semibold text-sm text-gray-700 dark:text-gray-200">
        <div class="flex items-center justify-between">
            <span class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: {{ $status['color'] }}"></span>
                {{ $status['title'] }}
            </span>
            <span class="text-xs rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5">
                {{ count($status['records']) }}
            </span>
        </div>
    </h3>

    <div data-status-id="{{ $status['id'] }}" class="flex flex-col flex-1 gap-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-xl min-h-[18rem]">
        @foreach($status['records'] as $record)
            @include(static::$recordView)
        @endforeach
    </div>
</div>
