<x-filament-widgets::widget>
    <div class="kpi-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="kpi-muted text-sm">
                    {{ $pace['label']
                        ? __('admin.kpi_dashboard.pace.title_month', ['month' => $pace['label']])
                        : __('admin.kpi_dashboard.pace.title_period') }}
                </p>

                @if ($pace['target'] > 0)
                    <p class="mt-1 flex items-baseline gap-2">
                        <span class="text-4xl font-extrabold tracking-tight">{{ number_format($pace['percent'], 1) }}%</span>
                        <span class="kpi-muted text-base">{{ __('admin.kpi_dashboard.pace.of_target', ['target' => number_format($pace['target'])]) }}</span>
                    </p>
                @else
                    <p class="kpi-muted mt-2 text-base">{{ __('admin.kpi_dashboard.pace.no_target') }}</p>
                @endif
            </div>

            <dl class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <div class="flex items-baseline gap-1.5">
                    <dt class="kpi-muted">{{ __('admin.kpi_dashboard.pace.achieved') }}</dt>
                    <dd class="font-bold">{{ number_format($pace['achieved']) }}</dd>
                </div>
                @if ($pace['target'] > 0)
                    <div class="flex items-baseline gap-1.5">
                        <dt class="kpi-muted">{{ __('admin.kpi_dashboard.pace.remaining') }}</dt>
                        <dd class="font-bold">{{ number_format($pace['remaining']) }}</dd>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <dt class="kpi-muted">{{ __('admin.kpi_dashboard.pace.remaining_days') }}</dt>
                        <dd class="font-bold">{{ $pace['remaining_working_days'] }}</dd>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <dt class="kpi-muted">{{ __('admin.kpi_dashboard.pace.required_daily') }}</dt>
                        <dd class="font-bold">{{ number_format($pace['required_daily']) }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($pace['target'] > 0)
            <div class="relative mt-8">
                <span
                    class="kpi-muted absolute -top-6 -translate-x-1/2 whitespace-nowrap text-xs rtl:translate-x-1/2"
                    style="inset-inline-start: {{ $pace['expected_percent'] }}%"
                >
                    {{ __('admin.kpi_dashboard.pace.expected', ['percent' => $pace['expected_percent']]) }}
                </span>

                <div class="kpi-track h-3 w-full overflow-hidden rounded-full">
                    <div class="kpi-fill h-3 rounded-full transition-all duration-700" style="width: {{ min(100, $pace['percent']) }}%"></div>
                </div>

                <span
                    class="absolute -top-1 h-5 w-0.5 rounded-full bg-current opacity-70"
                    style="inset-inline-start: {{ $pace['expected_percent'] }}%"
                ></span>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
