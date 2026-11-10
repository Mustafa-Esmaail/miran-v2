<?php

namespace App\Filament\Widgets;

use App\Enums\CrmActivityType;
use App\Models\CrmActivity;
use App\Traits\CrmRoleScope;
use Filament\Widgets\ChartWidget;

class CrmActivityBreakdownWidget extends ChartWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected static ?int $sort = 5;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_activities_breakdown_heading');
    }

    protected function getData(): array
    {
        $employee = $this->currentEmployee();
        $monthStart = now()->startOfMonth();

        $typeColors = [
            'call'      => '#1976D2',
            'email'     => '#7B2BC9',
            'whatsapp'  => '#23C997',
            'meeting'   => '#FF9100',
            'note'      => '#7E7E7E',
            'visit'     => '#99745C',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (CrmActivityType::cases() as $type) {
            $count = $this->scopeActivities(
                CrmActivity::query()
                    ->where('type', $type)
                    ->where('created_at', '>=', $monthStart),
                $employee
            )->count();

            $labels[] = __('admin.options.'.$type->value);
            $data[] = $count;
            $colors[] = $typeColors[$type->value] ?? '#7E7E7E';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 5,
                    'borderWidth' => 0,
                    'barThickness' => 18,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0, 'font' => ['size' => 11]],
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                ],
                'y' => [
                    'grid' => ['display' => false],
                    'ticks' => ['font' => ['size' => 12]],
                ],
            ],
        ];
    }
}
