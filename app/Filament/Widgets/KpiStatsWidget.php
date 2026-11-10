<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class KpiStatsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.kpi-stats';

    protected int|string|array $columnSpan = ['default' => 12];

    protected function getViewData(): array
    {
        $service = app(KpiDashboardService::class);
        $filters = $this->pageFilters ?? [];

        return [
            'kpis' => $service->kpis($filters),
            'callQuality' => $service->callQuality($filters),
        ];
    }
}
