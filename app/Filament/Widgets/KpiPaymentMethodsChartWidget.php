<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class KpiPaymentMethodsChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    private const COLORS = ['#c99b78', '#9fb9ae', '#e2bfa3', '#7d8a85', '#6f8f84', '#b07d5b'];

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 5];

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('admin.kpi_dashboard.payment_methods_chart.heading');
    }

    public function getDescription(): ?string
    {
        return __('admin.kpi_dashboard.payment_methods_chart.description');
    }

    protected function getData(): array
    {
        $methods = app(KpiDashboardService::class)->paymentMethods($this->pageFilters ?? []);

        return [
            'datasets' => [
                [
                    'data' => array_values($methods),
                    'backgroundColor' => array_slice(
                        array_merge(self::COLORS, self::COLORS),
                        0,
                        max(1, count($methods)),
                    ),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($methods),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '62%',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
