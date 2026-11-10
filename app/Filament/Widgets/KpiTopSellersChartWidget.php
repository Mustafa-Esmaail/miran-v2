<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KpiTopSellersChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected ?string $maxHeight = '380px';

    public function getHeading(): ?string
    {
        return __('admin.kpi_dashboard.top_sellers.heading');
    }

    public function getDescription(): ?string
    {
        return __('admin.kpi_dashboard.top_sellers.description');
    }

    protected function getData(): array
    {
        $sellers = app(KpiDashboardService::class)->topSellers($this->pageFilters ?? []);

        return [
            'datasets' => [
                [
                    'label' => __('admin.kpi_dashboard.top_sellers.total'),
                    'data' => $sellers->pluck('total')->all(),
                    'backgroundColor' => '#c99b78',
                    'borderRadius' => 2,
                    'maxBarThickness' => 20,
                ],
            ],
            'labels' => $sellers->pluck('name')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
