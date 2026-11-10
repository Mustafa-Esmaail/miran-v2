<x-filament-widgets::widget>
    <div class="rounded-2xl border border-default bg-surface p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <h2 class="text-base font-semibold text-primary-700">{{ __('admin.dashboard_widgets.quick_actions.heading') }}</h2>
                <p class="text-sm text-muted">{{ __('admin.dashboard_widgets.quick_actions.description') }}</p>
            </div>
            <div class="rounded-lg border border-default bg-elevated p-2">
                <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-primary-600" />
            </div>
        </div>

        @php($actions = $this->getActions())

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="group flex items-center justify-between rounded-xl border border-default bg-elevated px-4 py-3 transition hover:border-primary-300 hover:bg-primary-50/60"
                >
                    <span class="text-sm font-medium text-primary-700 transition group-hover:text-primary-600">
                        {{ $action['label'] }}
                    </span>
                    <x-filament::icon :icon="$action['icon']" class="h-5 w-5 text-primary-500 transition group-hover:text-primary-600" />
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-default px-4 py-6 text-sm text-muted sm:col-span-2 xl:col-span-3">
                    {{ __('admin.dashboard_widgets.quick_actions.empty') }}
                </div>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
