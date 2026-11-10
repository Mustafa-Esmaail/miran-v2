<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <x-filament::input.wrapper class="flex-1 min-w-[12rem]">
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.labels.search_placeholder')"
                />
            </x-filament::input.wrapper>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.labels.from') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="dateFrom"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.labels.until') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="date"
                        wire:model.live="dateTo"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.labels.assigned_to') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="assignedTo">
                        <option value="">{{ __('admin.labels.all') }}</option>

                        @foreach ($assigneeOptions as $userId => $assigneeName)
                            <option value="{{ $userId }}">{{ $assigneeName }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <div x-data wire:ignore.self class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>
</x-filament-panels::page>
