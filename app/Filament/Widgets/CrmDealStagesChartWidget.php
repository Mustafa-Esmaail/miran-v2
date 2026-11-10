<?php

namespace App\Filament\Widgets;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Traits\CrmRoleScope;
use Filament\Widgets\ChartWidget;

class CrmDealStagesChartWidget extends ChartWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 4];

    protected static ?int $sort = 4;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_deal_stages_heading');
    }

    protected function getData(): array
    {
        $employee = $this->currentEmployee();

        $stageColors = [
            'prospecting' => '#7B2BC9',
            'qualification' => '#1976D2',
            'proposal' => '#FFD465',
            'negotiation' => '#FF9100',
            'closed_won' => '#23C997',
            'closed_lost' => '#F44E4E',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (DealStage::cases() as $stage) {
            $count = $this->scopeDeals(Deal::query(), $employee)
                ->where('stage', $stage)
                ->count();

            if ($count > 0) {
                $labels[] = __('admin.options.'.$stage->value);
                $data[] = $count;
                $colors[] = $stageColors[$stage->value] ?? '#7E7E7E';
            }
        }

        if (empty($data)) {
            $labels = [__('admin.dashboard_widgets.no_data')];
            $data = [1];
            $colors = ['#E5E7EB'];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 6,
                    'borderWidth' => 3,
                    'borderColor' => 'rgba(255,255,255,0)',
                    'hoverBorderColor' => 'rgba(255,255,255,0.8)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '68%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 14,
                        'usePointStyle' => true,
                        'pointStyleWidth' => 8,
                        'font' => ['size' => 11],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
