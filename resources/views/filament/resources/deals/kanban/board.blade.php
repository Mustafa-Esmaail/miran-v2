<x-filament-panels::page>
    <div class="space-y-4">
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('admin.models.deals') }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('admin.pages.manage_kanban_board') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-filament::button color="gray"
                            wire:click="$set('search', ''); $set('employeeId', ''); $set('companyId', ''); $set('pipeline', 'all')">
                            {{ __('admin.actions.reset_filters') }}
                        </x-filament::button>

                        <x-filament::button tag="a" href="{{ route('filament.admin.resources.deals.create') }}">
                            {{ __('admin.actions.create') }}
                        </x-filament::button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" wire:model.live.debounce.300ms="search" :placeholder="__('admin.labels.search_placeholder')" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="employeeId">
                            <option value="">
                                {{ __('admin.labels.assigned_to') }} — {{ __('admin.labels.all') }}
                            </option>

                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="companyId">
                            <option value="">
                                {{ __('admin.labels.company') }} — {{ __('admin.labels.all') }}
                            </option>

                            @foreach ($companies as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="pipeline">
                            @foreach ($pipelineOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ __('admin.labels.stage') }} — {{ $label }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <div x-data wire:ignore.self class="overflow-x-auto overflow-y-hidden">
            <div class="flex min-w-max items-start gap-4 pb-4">
                @foreach ($statuses as $status)
                    @include(static::$statusView)
                @endforeach
            </div>

            <div wire:ignore>
                @include(static::$scriptsView)
            </div>
        </div>
    </div>
</x-filament-panels::page>
