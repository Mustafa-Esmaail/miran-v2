<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersReportIncomeChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected ?string $maxHeight = '520px';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.orders_report_income_heading');
    }

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];

        $rows = Order::query()
            ->filteredForReport(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
                $filters['payment_method'] ?? null,
            )
            ->where('status', OrderStatus::Paid)
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('admin.dashboard_widgets.orders_report_income_heading'),
                    'data' => $rows->pluck('total')->map(fn ($value): float => round((float) $value, 2))->all(),
                    'borderColor' => '#99745c',
                    'backgroundColor' => 'rgba(153, 116, 92, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $rows->pluck('day')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
