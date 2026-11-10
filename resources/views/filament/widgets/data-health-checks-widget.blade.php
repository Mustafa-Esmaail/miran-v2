<x-filament-widgets::widget>
    <div class="rounded-2xl border border-default bg-surface p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <h2 class="text-base font-semibold text-primary-700">{{ __('admin.dashboard_widgets.health_checks.heading') }}</h2>
                <p class="text-sm text-muted">{{ __('admin.dashboard_widgets.health_checks.description') }}</p>
            </div>
            <div class="rounded-lg border border-default bg-elevated p-2">
                <x-filament::icon icon="heroicon-o-heart" class="h-5 w-5 text-success-600" />
            </div>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach ($this->getChecks() as $check)
                <div class="rounded-xl border border-default bg-elevated p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-primary-700">{{ $check['title'] }}</h3>
                        <span class="rounded-full bg-warning-100 px-2 py-0.5 text-xs font-semibold text-warning-700">
                            {{ $check['total'] }}
                        </span>
                    </div>

                    @if (count($check['items']) > 0)
                        <ul class="mt-3 grid gap-2">
                            @foreach ($check['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="text-sm text-primary-600 hover:text-primary-500 hover:underline">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-success-600">{{ __('admin.dashboard_widgets.health_checks.no_issues') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
