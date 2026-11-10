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

    <div x-data="{ draggingTaskId: null }" class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        @foreach ($columns as $column)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ $column['label'] }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">{{ $column['count'] }}</span>
                    </div>
                </x-slot>

                <div
                    class="space-y-3 rounded-xl border border-dashed border-transparent p-1 transition"
                    @dragover.prevent
                    @drop.prevent="if (draggingTaskId) { $wire.moveTask(draggingTaskId, '{{ $column['status']->value }}'); draggingTaskId = null }"
                >
                    @forelse ($column['items'] as $task)
                        @php
                            $entityUrl = CrmEntityLink::editUrl($task->entity_type, $task->entity_id);
                        @endphp
                        <div
                            draggable="true"
                            @dragstart="draggingTaskId = {{ $task->id }}"
                            @dragend="draggingTaskId = null"
                            class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $task->title }}</p>
                                @if (! $task->is_done)
                                    <x-filament::button size="xs" color="success" wire:click="markDone({{ $task->id }})">
                                        {{ __('admin.actions.mark_done') }}
                                    </x-filament::button>
                                @endif
                            </div>

                            <div class="mt-2 text-xs text-gray-500">
                                <p>{{ __('admin.labels.assigned_to') }}: {{ $task->employee?->name ?? '-' }}</p>
                                <p>{{ __('admin.labels.due_at') }}: {{ $task->due_at?->format('Y-m-d H:i') ?? '-' }}</p>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-2 text-xs">
                                <span class="text-gray-500">#{{ $task->id }}</span>
                                <div class="flex items-center gap-2">
                                    @if ($entityUrl)
                                        <a href="{{ $entityUrl }}" class="font-semibold text-primary-600 hover:text-primary-500">
                                            {{ __('admin.labels.related_to') }} #{{ $task->entity_id }}
                                        </a>
                                    @endif
                                    <a href="{{ route('filament.admin.resources.crm-tasks.edit', $task->id) }}" class="font-semibold text-gray-700 hover:text-gray-600 dark:text-gray-300">
                                        {{ __('admin.actions.edit') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-xs text-gray-400 dark:border-gray-700">{{ __('admin.empty_states.crm_tasks.heading') }}</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
