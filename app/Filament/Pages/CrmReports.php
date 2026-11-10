<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmDealsByStageWidget;
use App\Filament\Widgets\CrmFunnelWidget;
use App\Filament\Widgets\CrmSourceBreakdownWidget;
use App\Filament\Widgets\CrmTeamLeaderboardWidget;
use Filament\Pages\Page;

class CrmReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.crm-reports';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.crm_reports');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.request_reports');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('crm_reports.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.crm_reports');
    }

    public function getHeaderWidgets(): array
    {
        return [
            CrmFunnelWidget::class,
            CrmSourceBreakdownWidget::class,
            CrmDealsByStageWidget::class,
            CrmTeamLeaderboardWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }
}
