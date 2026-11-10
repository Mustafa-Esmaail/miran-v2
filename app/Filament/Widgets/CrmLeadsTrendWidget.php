<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use App\Traits\CrmRoleScope;
use Filament\Widgets\ChartWidget;

class CrmLeadsTrendWidget extends ChartWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 8];

    protected static ?int $sort = 3;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_leads_trend_heading');
    }

    protected function getData(): array
    {
        $employee = $this->currentEmployee();
        $days = 14;
        $labels = [];
        $newLeads = [];
        $converted = [];

        $baseNew = $this->scopeLeads(ServiceRequest::query(), $employee);
        $baseConverted = $this->scopeLeads(ServiceRequest::query()->whereNotNull('converted_at'), $employee);

        $newByDay = (clone $baseNew)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $convertedByDay = (clone $baseConverted)
            ->where('converted_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(converted_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->format('M d');
            $newLeads[] = (int) $newByDay->get($day->toDateString(), 0);
            $converted[] = (int) $convertedByDay->get($day->toDateString(), 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('admin.dashboard_widgets.new_leads_label'),
                    'data' => $newLeads,
                    'borderColor' => '#7B2BC9',
                    'backgroundColor' => 'rgba(123,43,201,0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#7B2BC9',
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => __('admin.dashboard_widgets.converted_label'),
                    'data' => $converted,
                    'borderColor' => '#23C997',
                    'backgroundColor' => 'rgba(35,201,151,0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#23C997',
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyleWidth' => 8,
                        'padding' => 16,
                        'font' => ['size' => 12],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['font' => ['size' => 11]],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0, 'font' => ['size' => 11]],
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                ],
            ],
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'elements' => ['line' => ['borderWidth' => 2]],
        ];
    }
}
