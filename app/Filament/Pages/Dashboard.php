<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminChatQuickAccessWidget;
use App\Filament\Widgets\DataHealthChecksWidget;
use App\Filament\Widgets\FailedPaymentsQueueWidget;
use App\Filament\Widgets\InstructorSessionsWidget;
use App\Filament\Widgets\LatestCommentsWidget;
use App\Filament\Widgets\OpsKpiOverviewWidget;
use App\Filament\Widgets\OpsQuickActionsWidget;
use App\Filament\Widgets\OpsTrendComparisonChartWidget;
use App\Filament\Widgets\PendingBankTransferQueueWidget;
use App\Filament\Widgets\PendingBlogCommentsQueueWidget;
use App\Filament\Widgets\PendingCertificatesQueueWidget;
use App\Filament\Widgets\PendingCourseReviewsQueueWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.dashboard');
    }

    public function getTitle(): string
    {
        return __('admin.dashboard.title');
    }

    public function getHeaderWidgets(): array
    {
        return [
            InstructorSessionsWidget::class,
            LatestCommentsWidget::class,
            // OpsKpiOverviewWidget::class,
            AdminChatQuickAccessWidget::class,

        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 6,
            'xl' => 6,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            // OpsQuickActionsWidget::class,

            // PendingBankTransferQueueWidget::class,
            // PendingCertificatesQueueWidget::class,
            // PendingCourseReviewsQueueWidget::class,
            // PendingBlogCommentsQueueWidget::class,
            // FailedPaymentsQueueWidget::class,
            // OpsTrendComparisonChartWidget::class,
            // DataHealthChecksWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }
}
