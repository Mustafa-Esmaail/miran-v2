<x-filament-panels::page>
    <div class="flex justify-end">
        <x-filament::button tag="a" href="{{ route('filament.admin.resources.deals.create') }}">
            {{ __('admin.actions.create') }}
        </x-filament::button>
    </div>

    <x-filament::section>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.labels.search_placeholder')"
                />
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
                <x-filament::input.select wire:model.live="companyId">
                    <option value="">{{ __('admin.labels.company') }}: {{ __('admin.labels.all_courses') }}</option>
                    @foreach ($companies as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="pipeline">
                    @foreach ($pipelineOptions as $value => $label)
                        <option value="{{ $value }}">{{ __('admin.labels.stage') }}: {{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </x-filament::section>

    <div x-data="{ draggingDealId: null }" class="grid grid-cols-1 gap-4 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach ($columns as $column)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ $column['label'] }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">{{ $column['count'] }}</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    {{ __('admin.labels.deal_value') }}: SAR {{ number_format($column['value'] / 100, 0) }}
                </x-slot>

                <div
                    class="space-y-3 rounded-xl border border-dashed border-transparent p-1 transition"
                    @dragover.prevent
                    @drop.prevent="if (draggingDealId) { $wire.moveDeal(draggingDealId, '{{ $column['stage']->value }}'); draggingDealId = null }"
                >
                    @forelse ($column['items'] as $deal)
                        <div
                            draggable="true"
                            @dragstart="draggingDealId = {{ $deal->id }}"
                            @dragend="draggingDealId = null"
                            class="rounded-xl border border-gray-200 bg-white p-3 transition hover:border-primary-300 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                            <a href="{{ route('filament.admin.resources.deals.edit', $deal->id) }}" class="block">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $deal->title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $deal->company?->name ?? '-' }}</p>
                                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ __('admin.labels.assigned_to') }}: {{ $deal->employee?->name ?? '-' }}</span>
                                    <span>#{{ $deal->id }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ __('admin.labels.expected_close_date') }}: {{ $deal->expected_close_date?->format('Y-m-d') ?? '-' }}</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">SAR {{ number_format($deal->value / 100, 0) }}</span>
                                </div>
                            </a>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-xs text-gray-400 dark:border-gray-700">{{ __('admin.empty_states.deals.heading') }}</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
