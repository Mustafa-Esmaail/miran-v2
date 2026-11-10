<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class LmsEnrollmentTrendWidget extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.lms_enrollment_trend_heading');
    }

    protected function getData(): array
    {
        $now = CarbonImmutable::now();
        $days = 14;
        $start = $now->subDays($days - 1)->startOfDay();

        $raw = Enrollment::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->subDays($i)->toDateString();
            $labels[] = $now->subDays($i)->format('d M');
            $data[] = (int) $raw->get($day, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('admin.dashboard_widgets.lms_stats.enrollments_label'),
                    'data' => $data,
                    'backgroundColor' => '#6366f1',
                    'borderColor' => '#6366f1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
