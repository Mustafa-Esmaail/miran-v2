@props(['status'])

<div class="md:w-[24rem] flex-shrink-0 mb-5 md:min-h-full flex flex-col">
    <h3 class="mb-2 px-2 font-semibold text-sm text-gray-500 dark:text-gray-300">
        <div class="flex items-center justify-between">
            <span>{{ $status['title'] }}</span>
            <span class="text-xs rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5">
                {{ count($status['records']) }}
                @if(! is_null($status['limit']))
                    / {{ $status['limit'] }}
                @endif
            </span>
        </div>
    </h3>

    <div data-status-id="{{ $status['id'] }}" class="flex flex-col flex-1 gap-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-xl min-h-[18rem]">
        @foreach($status['records'] as $record)
            @include(static::$recordView)
        @endforeach
    </div>
</div>
