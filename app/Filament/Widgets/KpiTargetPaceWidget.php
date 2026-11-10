<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class KpiTargetPaceWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.kpi-target-pace';

    protected int|string|array $columnSpan = ['default' => 12];

    protected function getViewData(): array
    {
        return [
            'pace' => app(KpiDashboardService::class)->targetPace($this->pageFilters ?? []),
        ];
    }
}
