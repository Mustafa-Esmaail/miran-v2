<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Enrollment;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class OpsTrendComparisonChartWidget extends ChartWidget
{
    protected ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.trend_heading');
    }

    protected function getData(): array
    {
        $now = CarbonImmutable::now();

        $currentStart = $now->subDays(6)->startOfDay();
        $currentEnd = $now->endOfDay();
        $previousStart = $currentStart->subDays(7);
        $previousEnd = $currentStart->subDay()->endOfDay();

        $currentOrders = Order::query()
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();

        $previousOrders = Order::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $currentRevenue = (int) Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('total_amount');

        $previousRevenue = (int) Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');

        $currentEnrollments = Enrollment::query()
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();

        $previousEnrollments = Enrollment::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        return [
            'datasets' => [
                [
                    'label' => __('admin.dashboard_widgets.trend_labels.current'),
                    'data' => [
                        $currentOrders,
                        round((float) $currentRevenue, 2),
                        $currentEnrollments,
                    ],
                    'backgroundColor' => ['#2563eb', '#16a34a', '#9333ea'],
                ],
                [
                    'label' => __('admin.dashboard_widgets.trend_labels.previous'),
                    'data' => [
                        $previousOrders,
                        round((float) $previousRevenue, 2),
                        $previousEnrollments,
                    ],
                    'backgroundColor' => ['#93c5fd', '#86efac', '#d8b4fe'],
                ],
            ],
            'labels' => [
                __('admin.dashboard_widgets.trend_labels.orders'),
                __('admin.dashboard_widgets.trend_labels.revenue'),
                __('admin.dashboard_widgets.trend_labels.enrollments'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
