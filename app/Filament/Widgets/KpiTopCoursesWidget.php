<?php

namespace App\Filament\Widgets;

use App\Services\KpiDashboardService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class KpiTopCoursesWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.kpi-top-courses';

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected function getViewData(): array
    {
        $courses = app(KpiDashboardService::class)->topCourses($this->pageFilters ?? []);

        return [
            'courses' => $courses,
            'maxTotal' => max(1, (int) $courses->max('total')),
        ];
    }
}
