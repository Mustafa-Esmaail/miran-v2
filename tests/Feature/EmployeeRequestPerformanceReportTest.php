<?php

namespace Tests\Feature;

use App\Filament\Pages\EmployeeRequestPerformanceReport;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeRequestPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeEmployee(): Employee
    {
        return Employee::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    public function test_it_denies_access_without_permission(): void
    {
        User::factory()->create();

        $this->assertFalse(EmployeeRequestPerformanceReport::canAccess());
    }

    public function test_it_opens_on_all_time_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['employee_request_performance_report.viewAny']);

        Livewire::test(EmployeeRequestPerformanceReport::class)
            ->assertSet('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->set('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString());
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['employee_request_performance_report.viewAny']);

        Livewire::test(EmployeeRequestPerformanceReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_this_month_preset_excludes_older_requests(): void
    {
        $this->actingAsAdminWithPermissions(['employee_request_performance_report.viewAny']);

        $employee = $this->makeEmployee();

        ServiceRequest::factory()->create(['assigned_to' => $employee->user_id]);
        ServiceRequest::factory()->create(['assigned_to' => $employee->user_id])
            ->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2)])
            ->saveQuietly();

        $allRows = Livewire::test(EmployeeRequestPerformanceReport::class)
            ->instance()
            ->getTableRecords();

        $this->assertSame(2, $allRows->firstWhere('employee', $employee->name)['total']);

        $monthRows = Livewire::test(EmployeeRequestPerformanceReport::class)
            ->set('filters.date_range', 'this_month')
            ->instance()
            ->getTableRecords();

        $this->assertSame(1, $monthRows->firstWhere('employee', $employee->name)['total']);
    }

    public function test_it_exports_csv_and_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['employee_request_performance_report.viewAny']);

        $employee = $this->makeEmployee();
        ServiceRequest::factory()->create(['assigned_to' => $employee->user_id]);

        Livewire::test(EmployeeRequestPerformanceReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(EmployeeRequestPerformanceReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }
}
