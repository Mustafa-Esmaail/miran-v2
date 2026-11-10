<?php

namespace Tests\Unit;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AdminChatQuickAccessWidget;
use App\Filament\Widgets\DataHealthChecksWidget;
use App\Filament\Widgets\FailedPaymentsQueueWidget;
use App\Filament\Widgets\InstructorSessionsWidget;
use App\Filament\Widgets\LatestCommentsWidget;
use App\Filament\Widgets\OpsQuickActionsWidget;
use App\Filament\Widgets\OpsTrendComparisonChartWidget;
use App\Filament\Widgets\PendingBankTransferQueueWidget;
use App\Filament\Widgets\PendingBlogCommentsQueueWidget;
use App\Filament\Widgets\PendingCertificatesQueueWidget;
use App\Filament\Widgets\PendingCourseReviewsQueueWidget;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminOpsDashboardCompositionTest extends TestCase
{
    #[Test]
    public function it_uses_the_new_admin_ops_cockpit_widgets(): void
    {
        $dashboard = new Dashboard;

        $this->assertSame([
            InstructorSessionsWidget::class,
            LatestCommentsWidget::class,
            AdminChatQuickAccessWidget::class,
        ], $dashboard->getHeaderWidgets());
        $this->assertSame([
            OpsQuickActionsWidget::class,
            PendingBankTransferQueueWidget::class,
            PendingCertificatesQueueWidget::class,
            PendingCourseReviewsQueueWidget::class,
            PendingBlogCommentsQueueWidget::class,
            FailedPaymentsQueueWidget::class,
            OpsTrendComparisonChartWidget::class,
            DataHealthChecksWidget::class,
        ], $dashboard->getFooterWidgets());
    }
}
