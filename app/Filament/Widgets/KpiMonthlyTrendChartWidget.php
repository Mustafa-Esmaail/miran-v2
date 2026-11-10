<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KpiMonthlyTrendChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 12];

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('admin.kpi_dashboard.monthly_trend.heading');
    }

    public function getDescription(): ?string
    {
        $service = app(KpiDashboardService::class);
        [, $to] = $service->range($this->pageFilters ?? []);

        return __('admin.kpi_dashboard.monthly_trend.description', ['month' => $to->translatedFormat('F Y')]);
    }

    protected function getData(): array
    {
        $trend = app(KpiDashboardService::class)->monthlyTrend($this->pageFilters ?? []);

        return [
            'datasets' => [
                [
                    'label' => __('admin.kpi_dashboard.monthly_trend.current'),
                    'data' => $trend['current'],
                    'borderColor' => '#c99b78',
                    'backgroundColor' => '#c99b78',
                    'borderWidth' => 2,
                    'pointRadius' => 3,
                    'tension' => 0.4,
                ],
                [
                    'label' => __('admin.kpi_dashboard.monthly_trend.previous'),
                    'data' => $trend['previous'],
                    'borderColor' => '#9fb9ae',
                    'backgroundColor' => '#9fb9ae',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'pointRadius' => 3,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
