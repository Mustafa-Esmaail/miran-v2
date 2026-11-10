@props([
    'label' => '',
    'items' => [],
    'color' => 'gray',
])

<div x-data="{ open: false }" class="mt-3">
    <button
        type="button"
        @click="open = !open"
        @class([
            'flex w-full items-center gap-1.5 text-xs font-medium',
            'text-warning-600 dark:text-warning-400' => $color === 'warning',
            'text-primary-600 dark:text-primary-400' => $color === 'primary',
            'text-danger-600 dark:text-danger-400' => $color === 'danger',
            'text-success-600 dark:text-success-400' => $color === 'success',
            'text-gray-600 dark:text-gray-400' => $color === 'gray',
        ])
    >
        <x-heroicon-o-chevron-down class="h-3.5 w-3.5 shrink-0 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
        <span>{{ $label }} ({{ count($items) }})</span>
    </button>

    <ul x-show="open" x-collapse class="mt-2 max-h-40 space-y-1 overflow-y-auto text-xs text-gray-600 dark:text-gray-300">
        @foreach ($items as $item)
            <li class="flex flex-col gap-0.5 rounded-md bg-gray-50 px-2 py-1 dark:bg-gray-800">
                <div class="flex justify-between gap-2">
                    <span>{{ $item['name'] }}</span>
                    <span class="text-gray-400">{{ $item['email'] }}</span>
                </div>
                @if (! empty($item['meta']))
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $item['meta'] }}</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
