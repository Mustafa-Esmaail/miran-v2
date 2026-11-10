<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KpiDailySalesChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 7];

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('admin.kpi_dashboard.daily_sales.heading');
    }

    public function getDescription(): ?string
    {
        return __('admin.kpi_dashboard.daily_sales.description');
    }

    protected function getData(): array
    {
        $daily = app(KpiDashboardService::class)->dailySales($this->pageFilters ?? []);

        return [
            'datasets' => [
                [
                    'label' => __('admin.kpi_dashboard.daily_sales.heading'),
                    'data' => $daily['data'],
                    'backgroundColor' => '#c99b78',
                    'borderRadius' => 3,
                    'maxBarThickness' => 14,
                ],
            ],
            'labels' => $daily['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
