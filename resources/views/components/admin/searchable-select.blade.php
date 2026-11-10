@props([
    'options'      => [],
    'wireModel'    => '',
    'currentValue' => '',
    'placeholder'  => '— ' . __('admin.labels.select') . ' —',
    'disabled'     => false,
])

@php
    $alpineOptions = collect($options)
        ->map(fn ($label, $value) => ['v' => (string) $value, 'l' => $label])
        ->values()
        ->all();
@endphp

<div
    x-data="{
        search: '',
        open: false,
        value: '{{ $currentValue }}',
        options: @js($alpineOptions),
        dropdownStyle: '',
        get filtered() {
            if (!this.search) return this.options;
            const s = this.search.toLowerCase();
            return this.options.filter(o => o.l.toLowerCase().includes(s));
        },
        positionDropdown() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const dropH = 260;
            const below = window.innerHeight - rect.bottom;
            if (below >= dropH || below >= rect.top) {
                this.dropdownStyle = `top:${rect.bottom + 4}px;left:${rect.left}px;width:${rect.width}px`;
            } else {
                this.dropdownStyle = `bottom:${window.innerHeight - rect.top + 4}px;left:${rect.left}px;width:${rect.width}px`;
            }
        },
        select(v) {
            this.value = v;
            this.search = '';
            this.open = false;
            $wire.set('{{ $wireModel }}', v ? (isNaN(v) ? v : Number(v)) : null);
        },
        get selectedLabel() {
            if (!this.value && this.value !== 0) return null;
            const opt = this.options.find(o => o.v === String(this.value));
            return opt ? opt.l : null;
        }
    }"
    @click.outside="open = false"
    @scroll.window="if (open) positionDropdown()"
    @resize.window="if (open) positionDropdown()"
    class="relative"
>
    {{-- Trigger button --}}
    <button
        type="button"
        x-ref="trigger"
        @click="if (!{{ $disabled ? 'true' : 'false' }}) { open = !open; if (open) { positionDropdown(); $nextTick(() => $refs.searchInput && $refs.searchInput.focus()) } }"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-start text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
    >
        <span
            :class="{ 'text-gray-400 dark:text-gray-500': !selectedLabel }"
            x-text="selectedLabel || '{{ $placeholder }}'"
            class="truncate"
        ></span>
        <svg class="ms-2 h-4 w-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-cloak
        :style="dropdownStyle"
        class="fixed z-9999 min-w-50 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        {{-- Search input --}}
        <div class="border-b border-gray-100 p-2 dark:border-gray-700">
            <input
                x-ref="searchInput"
                x-model="search"
                type="text"
                placeholder="{{ __('admin.labels.type_to_search') }}..."
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
            />
        </div>

        {{-- Options list --}}
        <div class="max-h-52 overflow-y-auto py-1">
            {{-- Clear / empty option --}}
            <button
                type="button"
                @click="select('')"
                class="w-full px-3 py-2 text-start text-sm text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50"
            >
                {{ $placeholder }}
            </button>

            <template x-for="opt in filtered" :key="opt.v">
                <button
                    type="button"
                    @click="select(opt.v)"
                    :class="{
                        'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300': opt.v === String(value),
                        'text-gray-700 dark:text-gray-200': opt.v !== String(value)
                    }"
                    class="w-full px-3 py-2 text-start text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    x-text="opt.l"
                ></button>
            </template>

            <div
                x-show="filtered.length === 0"
                class="px-3 py-2 text-sm text-gray-400"
            >
                {{ __('admin.labels.no_results') }}
            </div>
        </div>
    </div>
</div>
