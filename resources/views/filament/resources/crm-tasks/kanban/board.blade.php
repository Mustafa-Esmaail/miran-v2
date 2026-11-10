@php
    use App\Support\CrmEntityLink;
@endphp

<x-filament-panels::page>
    <div class="flex justify-end">
        <x-filament::button tag="a" href="{{ route('filament.admin.resources.crm-tasks.create') }}">
            {{ __('admin.actions.create') }}
        </x-filament::button>
    </div>

    <x-filament::section>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model.live.debounce.300ms="search" :placeholder="__('admin.labels.search_placeholder')"/>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="employeeId">
                    <option value="">{{ __('admin.labels.assigned_to') }}: {{ __('admin.labels.all_courses') }}</option>
                    @foreach ($employees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="entityType">
                    @foreach ($entityOptions as $value => $label)
                        <option value="{{ $value }}">{{ __('admin.labels.related_to') }}: {{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="statusFilter">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ __('admin.labels.status') }}: {{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="dueScope">
                    @foreach ($dueOptions as $value => $label)
                        <option value="{{ $value }}">{{ __('admin.labels.due_at') }}: {{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <div x-data wire:ignore.self class="md:flex overflow-x-auto overflow-y-hidden gap-4 pb-4">
        @foreach($statuses as $status)
            @include(static::$statusView)
        @endforeach

        <div wire:ignore>
            @include(static::$scriptsView)
        </div>
    </div>
</x-filament-panels::page>
