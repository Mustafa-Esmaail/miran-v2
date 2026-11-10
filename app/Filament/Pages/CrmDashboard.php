<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmActivityBreakdownWidget;
use App\Filament\Widgets\CrmDealPipelineWidget;
use App\Filament\Widgets\CrmDealStagesChartWidget;
use App\Filament\Widgets\CrmLeadStatsWidget;
use App\Filament\Widgets\CrmLeadsTrendWidget;
use App\Filament\Widgets\CrmMyTasksWidget;
use App\Filament\Widgets\SalesTargetProgressWidget;
use Filament\Pages\Page;

class CrmDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.crm-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.crm_dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('crm_dashboard.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.crm_dashboard');
    }

    public function getHeaderWidgets(): array
    {
        return [
            // Row 1: KPI stats side by side (6+6)
            CrmLeadStatsWidget::class,
            CrmDealPipelineWidget::class,
            // Row 2: Trend line (8) + Doughnut (4)
            CrmLeadsTrendWidget::class,
            CrmDealStagesChartWidget::class,
            // Row 3: Activities bar (6) + Target progress bars (6)
            CrmActivityBreakdownWidget::class,
            SalesTargetProgressWidget::class,
            // Row 4: Tasks summary (full width)
            CrmMyTasksWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 12,
            'xl' => 12,
        ];
    }
}
