<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDashboardTranslationsTest extends TestCase
{
    public function test_admin_dashboard_widgets_use_translation_keys(): void
    {
        $files = [
            'app/Filament/Widgets/OpsKpiOverviewWidget.php',
            'app/Filament/Widgets/OpsTrendComparisonChartWidget.php',
            'app/Filament/Widgets/DataHealthChecksWidget.php',
            'app/Filament/Widgets/FailedPaymentsQueueWidget.php',
            'app/Filament/Widgets/PendingBankTransferQueueWidget.php',
            'app/Filament/Widgets/PendingCertificatesQueueWidget.php',
            'app/Filament/Widgets/PendingCourseReviewsQueueWidget.php',
            'app/Filament/Widgets/PendingBlogCommentsQueueWidget.php',
            'app/Filament/Widgets/OpsQuickActionsWidget.php',
            'resources/views/filament/widgets/ops-quick-actions-widget.blade.php',
            'resources/views/filament/widgets/data-health-checks-widget.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('admin.dashboard_widgets', $contents, $file);
        }
    }
}
