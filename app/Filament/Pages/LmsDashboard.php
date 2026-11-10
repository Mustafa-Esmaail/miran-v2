<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LmsEnrollmentTrendWidget;
use App\Filament\Widgets\LmsQuizPerformanceWidget;
use App\Filament\Widgets\LmsStatsWidget;
use App\Filament\Widgets\LmsTopCoursesWidget;
use App\Filament\Widgets\PendingCertificatesQueueWidget;
use Filament\Pages\Page;

class LmsDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.lms-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.lms_dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('lms_dashboard.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.lms_dashboard');
    }

    public function getHeaderWidgets(): array
    {
        return [
            LmsStatsWidget::class,
            LmsEnrollmentTrendWidget::class,
            LmsTopCoursesWidget::class,
            PendingCertificatesQueueWidget::class,
            LmsQuizPerformanceWidget::class,
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
