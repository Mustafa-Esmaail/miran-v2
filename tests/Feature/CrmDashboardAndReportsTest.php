<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Filament\Pages\CrmDashboard;
use App\Filament\Pages\CrmReports;
use App\Filament\Widgets\CrmActivityBreakdownWidget;
use App\Filament\Widgets\CrmDealPipelineWidget;
use App\Filament\Widgets\CrmDealsByStageWidget;
use App\Filament\Widgets\CrmDealStagesChartWidget;
use App\Filament\Widgets\CrmFunnelWidget;
use App\Filament\Widgets\CrmLeadStatsWidget;
use App\Filament\Widgets\CrmLeadsTrendWidget;
use App\Filament\Widgets\CrmMyTasksWidget;
use App\Filament\Widgets\CrmSourceBreakdownWidget;
use App\Filament\Widgets\CrmTeamLeaderboardWidget;
use App\Filament\Widgets\SalesTargetProgressWidget;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\SalesTarget;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrmDashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function seedCrmData(User $admin): void
    {
        $employee = Employee::factory()->create(['user_id' => $admin->id]);

        ServiceRequest::factory()->count(3)->create(['assigned_to' => $admin->id]);

        Deal::factory()->stage(DealStage::Prospecting)->create(['employee_id' => $employee->id]);
        Deal::factory()->stage(DealStage::Negotiation)->create(['employee_id' => $employee->id]);
        $wonDeal = Deal::factory()->stage(DealStage::ClosedWon)->create(['employee_id' => $employee->id]);

        CrmActivity::factory()->count(2)->create([
            'entity_id' => $wonDeal->id,
            'employee_id' => $employee->id,
        ]);

        CrmTask::factory()->create(['entity_id' => $wonDeal->id, 'employee_id' => $employee->id]);
        CrmTask::factory()->overdue()->create(['entity_id' => $wonDeal->id, 'employee_id' => $employee->id]);
        CrmTask::factory()->done()->create(['entity_id' => $wonDeal->id, 'employee_id' => $employee->id]);

        SalesTarget::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_crm_dashboard_is_denied_without_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'admin');

        $this->assertFalse(CrmDashboard::canAccess());
    }

    public function test_crm_reports_is_denied_without_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'admin');

        $this->assertFalse(CrmReports::canAccess());
    }

    public function test_crm_dashboard_renders_for_permitted_admin(): void
    {
        $this->actingAsAdminWithPermissions(['crm_dashboard.viewAny']);

        $this->assertTrue(CrmDashboard::canAccess());

        Livewire::test(CrmDashboard::class)
            ->assertOk()
            ->assertSee(__('admin.navigation.crm_dashboard'));
    }

    public function test_crm_reports_renders_for_permitted_admin(): void
    {
        $this->actingAsAdminWithPermissions(['crm_reports.viewAny']);

        $this->assertTrue(CrmReports::canAccess());

        Livewire::test(CrmReports::class)
            ->assertOk()
            ->assertSee(__('admin.navigation.crm_reports'));
    }

    public function test_dashboard_and_reports_register_the_expected_widgets(): void
    {
        $this->actingAsAdminWithPermissions(['crm_dashboard.viewAny', 'crm_reports.viewAny']);

        $this->assertSame([
            CrmLeadStatsWidget::class,
            CrmDealPipelineWidget::class,
            CrmLeadsTrendWidget::class,
            CrmDealStagesChartWidget::class,
            CrmActivityBreakdownWidget::class,
            SalesTargetProgressWidget::class,
            CrmMyTasksWidget::class,
        ], (new CrmDashboard)->getHeaderWidgets());

        $this->assertSame([
            CrmFunnelWidget::class,
            CrmSourceBreakdownWidget::class,
            CrmDealsByStageWidget::class,
            CrmTeamLeaderboardWidget::class,
        ], (new CrmReports)->getHeaderWidgets());
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function crmWidgets(): array
    {
        return [
            'lead stats' => [CrmLeadStatsWidget::class],
            'leads trend' => [CrmLeadsTrendWidget::class],
            'deal pipeline' => [CrmDealPipelineWidget::class],
            'deal stages chart' => [CrmDealStagesChartWidget::class],
            'deals by stage' => [CrmDealsByStageWidget::class],
            'funnel' => [CrmFunnelWidget::class],
            'source breakdown' => [CrmSourceBreakdownWidget::class],
            'activity breakdown' => [CrmActivityBreakdownWidget::class],
            'my tasks' => [CrmMyTasksWidget::class],
            'team leaderboard' => [CrmTeamLeaderboardWidget::class],
            'sales target progress' => [SalesTargetProgressWidget::class],
        ];
    }

    /**
     * @param  class-string  $widget
     */
    #[DataProvider('crmWidgets')]
    public function test_widget_renders_with_an_empty_database(string $widget): void
    {
        $this->actingAsAdminWithPermissions(['crm_dashboard.viewAny', 'crm_reports.viewAny']);

        Livewire::test($widget)->assertOk();
    }

    /**
     * @param  class-string  $widget
     */
    #[DataProvider('crmWidgets')]
    public function test_widget_renders_with_crm_data(string $widget): void
    {
        $admin = $this->actingAsAdminWithPermissions(['crm_dashboard.viewAny', 'crm_reports.viewAny']);
        $this->seedCrmData($admin);

        Livewire::test($widget)->assertOk();
    }

    public function test_sales_target_widget_lists_the_employee_target_for_the_current_month(): void
    {
        $admin = $this->actingAsAdminWithPermissions(['crm_dashboard.viewAny']);
        $employee = Employee::factory()->create(['user_id' => $admin->id, 'name' => 'Target Owner']);
        SalesTarget::factory()->create(['employee_id' => $employee->id, 'target_revenue' => 50_000]);
        SalesTarget::factory()->create(['period' => now()->subYear()->format('Y-m')]);

        Livewire::test(SalesTargetProgressWidget::class)
            ->assertOk()
            ->assertSee('Target Owner')
            ->assertViewHas('rows', fn (array $rows): bool => count($rows) === 1)
            ->assertViewHas('totalTarget', 50_000);
    }
}
