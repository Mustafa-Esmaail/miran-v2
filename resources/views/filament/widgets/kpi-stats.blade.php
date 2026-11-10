@php
    $changeClass = fn (?float $value): string => $value === null ? 'kpi-muted' : ($value >= 0 ? 'kpi-up' : 'kpi-down');
    $signed = fn (float $value): string => ($value > 0 ? '+' : '').number_format($value, 1).'%';
    $hasCallQuality = $callQuality !== null;
    $valueClass = $hasCallQuality ? 'text-2xl xl:text-3xl' : 'text-3xl';
@endphp

<x-filament-widgets::widget>
    <div @class(['kpi-card grid grid-cols-1', 'md:grid-cols-5' => $hasCallQuality, 'md:grid-cols-4' => ! $hasCallQuality])>
        <div class="kpi-stat min-w-0 p-5">
            <p class="kpi-muted text-sm">{{ __('admin.kpi_dashboard.stats.revenue') }}</p>
            <p class="mt-2 {{ $valueClass }} font-extrabold tracking-tight">{{ number_format($kpis['revenue']) }}</p>
            <p class="mt-1 text-xs {{ $changeClass($kpis['revenue_change']) }}">
                @if ($kpis['revenue_change'] === null)
                    {{ __('admin.kpi_dashboard.stats.no_previous') }}
                @else
                    {{ $kpis['revenue_change'] >= 0 ? '▲' : '▼' }}
                    {{ __('admin.kpi_dashboard.stats.revenue_change', ['percent' => number_format(abs($kpis['revenue_change']), 1), 'days' => $kpis['previous_days']]) }}
                @endif
            </p>
        </div>

        <div class="kpi-stat min-w-0 p-5">
            <p class="kpi-muted text-sm">{{ __('admin.kpi_dashboard.stats.orders') }}</p>
            <p class="mt-2 {{ $valueClass }} font-extrabold tracking-tight">{{ number_format($kpis['orders']) }}</p>
            <p class="kpi-muted mt-1 text-xs">{{ __('admin.kpi_dashboard.stats.average_order', ['amount' => number_format($kpis['average_order'])]) }}</p>
        </div>

        <div class="kpi-stat min-w-0 p-5">
            <p class="kpi-muted text-sm">{{ __('admin.kpi_dashboard.stats.yoy') }}</p>
            <p class="mt-2 {{ $valueClass }} font-extrabold tracking-tight {{ $changeClass($kpis['yoy_growth']) }}" dir="ltr">
                {{ $kpis['yoy_growth'] === null ? '—' : $signed($kpis['yoy_growth']) }}
            </p>
            <p class="kpi-muted mt-1 text-xs">{{ __('admin.kpi_dashboard.stats.last_year', ['amount' => number_format($kpis['last_year_revenue'])]) }}</p>
        </div>

        <div class="kpi-stat min-w-0 p-5">
            <p class="kpi-muted text-sm">{{ __('admin.kpi_dashboard.stats.leads') }}</p>
            <p class="mt-2 {{ $valueClass }} font-extrabold tracking-tight">{{ number_format($kpis['leads']) }}</p>
            <p class="kpi-muted mt-1 text-xs">{{ __('admin.kpi_dashboard.stats.conversion', ['percent' => number_format($kpis['conversion_rate'], 1)]) }}</p>
        </div>

        @if ($hasCallQuality)
            <div class="kpi-stat min-w-0 p-5" data-kpi="call-quality">
                <p class="kpi-muted text-sm">{{ __('admin.kpi_dashboard.stats.call_quality') }}</p>
                <p class="mt-2 {{ $valueClass }} font-extrabold tracking-tight" dir="ltr">
                    {{ $callQuality['average'] === null ? '—' : number_format($callQuality['average'], 1).'%' }}
                </p>
                <p class="kpi-muted mt-1 text-xs">
                    @if ($callQuality['average'] === null)
                        {{ __('admin.kpi_dashboard.stats.call_quality_none') }}
                    @else
                        {{ __('admin.kpi_dashboard.stats.call_quality_count', ['count' => number_format($callQuality['count'])]) }}
                        ·
                        <span class="font-semibold">{{ \App\Support\CallQuality\CallQualityLevels::label($callQuality['level']) }}</span>
                    @endif
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
