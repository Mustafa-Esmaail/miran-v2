<?php

namespace Tests\Unit;

use App\Filament\Widgets\DataHealthChecksWidget;
use App\Filament\Widgets\InstructorSessionsWidget;
use App\Filament\Widgets\LatestCommentsWidget;
use App\Filament\Widgets\OpsKpiOverviewWidget;
use App\Filament\Widgets\OpsTrendComparisonChartWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class AdminOpsDashboardWidgetsSmokeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function kpi_chart_and_health_widgets_handle_empty_database_without_errors(): void
    {
        $kpiWidget = new OpsKpiOverviewWidget;
        $kpiStats = $this->invokeProtectedMethod($kpiWidget, 'getStats');

        $chartWidget = new OpsTrendComparisonChartWidget;
        $chartData = $this->invokeProtectedMethod($chartWidget, 'getData');

        $healthWidget = new DataHealthChecksWidget;
        $checks = $healthWidget->getChecks();
        $instructorSessionsWidget = new InstructorSessionsWidget;
        $latestCommentsWidget = new LatestCommentsWidget;

        $this->assertIsArray($kpiStats);
        $this->assertNotEmpty($kpiStats);
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertIsArray($checks);
        $this->assertCount(5, $checks);
        $this->assertSame(__('admin.dashboard_widgets.queues.instructor_sessions.heading'), $instructorSessionsWidget->getHeading());
        $this->assertSame(__('admin.dashboard_widgets.queues.latest_comments.heading'), $latestCommentsWidget->getHeading());

        $instructorWidgetContents = file_get_contents(base_path('app/Filament/Widgets/InstructorSessionsWidget.php'));

        $this->assertIsString($instructorWidgetContents);
        $this->assertStringContainsString("TextColumn::make('join_url')", $instructorWidgetContents);
        $this->assertStringContainsString("TextColumn::make('recording_url')", $instructorWidgetContents);
        $this->assertStringContainsString("__('admin.actions.join')", $instructorWidgetContents);
        $this->assertStringContainsString("__('admin.actions.watch_recording')", $instructorWidgetContents);
        $this->assertStringContainsString("__('admin.actions.view_all')", $instructorWidgetContents);
        $this->assertStringContainsString('applyCourseTitleSearch', $instructorWidgetContents);
        $this->assertStringContainsString('applyInstructorSearch', $instructorWidgetContents);
        $this->assertStringContainsString('->badge()', $instructorWidgetContents);
        $this->assertStringContainsString('->openUrlInNewTab()', $instructorWidgetContents);
        $this->assertStringNotContainsString('->copyable()', $instructorWidgetContents);
    }

    protected function invokeProtectedMethod(object $instance, string $methodName): mixed
    {
        $method = new ReflectionMethod($instance, $methodName);
        $method->setAccessible(true);

        return $method->invoke($instance);
    }
}
