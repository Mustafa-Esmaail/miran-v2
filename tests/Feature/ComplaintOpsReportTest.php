<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Filament\Pages\ComplaintOpsReport;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComplaintOpsReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithReportAccess(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'complaint_ops_report.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_page_renders_with_red_flag_stats(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'complaint_late',
        ]);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'complaint_not_contacted',
        ]);

        Livewire::test(ComplaintOpsReport::class)
            ->assertOk()
            ->assertSee(__('admin.dashboard_widgets.stats.complaint_late'))
            ->assertSee(__('admin.dashboard_widgets.stats.complaint_not_contacted'));
    }

    public function test_it_only_counts_complaint_sourced_requests(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'complaint_late',
        ]);

        $page = Livewire::test(ComplaintOpsReport::class)->assertOk();

        $rows = $this->callBuildRows($page->instance());

        $this->assertSame(0, $rows->sum('total'));
    }

    public function test_per_agent_rows_include_resolution_and_red_flag_counts(): void
    {
        $admin = $this->actingAsAdminWithReportAccess();

        $agent = User::factory()->create(['name' => 'Agent Report Test']);
        \App\Models\Employee::factory()->create(['user_id' => $agent->id, 'name' => $agent->name]);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'complaint_late',
            'assigned_to' => $agent->id,
        ]);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'status' => 'complaint_invalid',
            'assigned_to' => $agent->id,
            'created_at' => now()->subHours(5),
            'updated_at' => now(),
        ]);

        $page = Livewire::test(ComplaintOpsReport::class)->assertOk();

        $row = $this->callBuildRows($page->instance())->firstWhere('employee', $agent->name);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['total']);
        $this->assertSame(1, $row['resolved']);
        $this->assertSame(1, $row['late']);
        $this->assertSame(0, $row['not_contacted']);
        $this->assertNotNull($row['avg_resolution_hours']);
    }

    public function test_status_filter_options_are_scoped_to_the_complaint_type(): void
    {
        $this->actingAsAdminWithReportAccess();

        $page = Livewire::test(ComplaintOpsReport::class)->assertOk();

        $options = $this->callComplaintStatusOptions($page->instance());

        $this->assertArrayHasKey('complaint_late', $options);
        $this->assertArrayHasKey('complaint_not_contacted', $options);
        $this->assertArrayHasKey('new', $options);
        $this->assertArrayNotHasKey('no_answer_2', $options);
        $this->assertArrayNotHasKey('payment_completed', $options);
    }

    public function test_department_filter_defaults_to_operations(): void
    {
        $this->actingAsAdminWithReportAccess();

        $operationsDepartment = \App\Models\Department::factory()->create(['code' => 'DEPT-OPS']);

        Livewire::test(ComplaintOpsReport::class)
            ->assertOk()
            ->assertSet('filters.department_id', $operationsDepartment->id);
    }

    public function test_team_filter_narrows_rows_to_the_selected_department(): void
    {
        $this->actingAsAdminWithReportAccess();

        $operationsDepartment = \App\Models\Department::factory()->create(['code' => 'DEPT-OPS']);
        $supportDepartment = \App\Models\Department::factory()->create(['name' => 'Support Dept For Test']);

        $opsTeam = \App\Models\Team::factory()->create(['department_id' => $operationsDepartment->id]);
        $supportTeam = \App\Models\Team::factory()->create(['department_id' => $supportDepartment->id]);

        $opsUser = User::factory()->create(['name' => 'Ops Employee']);
        \App\Models\Employee::factory()->create(['user_id' => $opsUser->id, 'name' => $opsUser->name, 'department_id' => $operationsDepartment->id, 'team_id' => $opsTeam->id]);

        $supportUser = User::factory()->create(['name' => 'Support Employee']);
        \App\Models\Employee::factory()->create(['user_id' => $supportUser->id, 'name' => $supportUser->name, 'department_id' => $supportDepartment->id, 'team_id' => $supportTeam->id]);

        $page = Livewire::test(ComplaintOpsReport::class)->assertOk();

        $rows = $this->callBuildRows($page->instance());
        $this->assertTrue($rows->contains('employee', 'Ops Employee'));
        $this->assertFalse($rows->contains('employee', 'Support Employee'));
    }

    public function test_it_opens_on_all_time_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(ComplaintOpsReport::class)
            ->assertSet('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->set('filters.date_range', 'yesterday')
            ->assertSet('filters.date_from', now()->subDay()->toDateString())
            ->assertSet('filters.date_to', now()->subDay()->toDateString());
    }

    public function test_it_exports_csv(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint, 'status' => 'new']);

        Livewire::test(ComplaintOpsReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint, 'status' => 'new']);

        Livewire::test(ComplaintOpsReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    private function callBuildRows(ComplaintOpsReport $page): \Illuminate\Support\Collection
    {
        $method = new ReflectionMethod(ComplaintOpsReport::class, 'buildRows');
        $method->setAccessible(true);

        return $method->invoke($page);
    }

    private function callComplaintStatusOptions(ComplaintOpsReport $page): array
    {
        $method = new ReflectionMethod(ComplaintOpsReport::class, 'complaintStatusOptions');
        $method->setAccessible(true);

        return $method->invoke($page);
    }
}
