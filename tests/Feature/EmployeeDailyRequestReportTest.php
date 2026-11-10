<?php

namespace Tests\Feature;

use App\Filament\Pages\EmployeeDailyRequestReport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeDailyRequestReportTest extends TestCase
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

    public function test_it_builds_a_daily_pivot_with_avg_sum_and_not_assigned_columns(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        $employeeA = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        $employeeB = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        $day1 = Carbon::parse('2026-08-03'); // Monday
        $day2 = Carbon::parse('2026-08-04'); // Tuesday

        ServiceRequest::factory()->count(2)->create(['assigned_to' => $employeeA->user_id])
            ->each(fn (ServiceRequest $r) => $r->forceFill(['created_at' => $day1])->saveQuietly());
        ServiceRequest::factory()->create(['assigned_to' => $employeeB->user_id])
            ->forceFill(['created_at' => $day1])->saveQuietly();
        ServiceRequest::factory()->create(['assigned_to' => null])
            ->forceFill(['created_at' => $day1])->saveQuietly();

        ServiceRequest::factory()->count(3)->create(['assigned_to' => $employeeB->user_id])
            ->each(fn (ServiceRequest $r) => $r->forceFill(['created_at' => $day2])->saveQuietly());

        $rows = Livewire::test(EmployeeDailyRequestReport::class)
            ->set('filters.date_from', $day1->toDateString())
            ->set('filters.date_to', $day2->toDateString())
            ->instance()
            ->getTableRecords();

        $row1 = $rows->firstWhere('day', $day1->toDateString().' ('.$day1->format('D').')');
        $row2 = $rows->firstWhere('day', $day2->toDateString().' ('.$day2->format('D').')');
        $sumRow = $rows->firstWhere('day', __('admin.labels.sum_per_employee'));
        $avgRow = $rows->firstWhere('day', __('admin.labels.avg_per_employee'));

        $this->assertSame(2, $row1['emp_'.$employeeA->id]);
        $this->assertSame(1, $row1['emp_'.$employeeB->id]);
        $this->assertSame(3, $row1['sum']);
        $this->assertSame(1.5, $row1['avg']);
        $this->assertSame(1, $row1['not_assigned']);

        $this->assertSame(0, $row2['emp_'.$employeeA->id]);
        $this->assertSame(3, $row2['emp_'.$employeeB->id]);
        $this->assertSame(3, $row2['sum']);
        $this->assertSame(0, $row2['not_assigned']);

        $this->assertSame(2, $sumRow['emp_'.$employeeA->id]);
        $this->assertSame(4, $sumRow['emp_'.$employeeB->id]);
        $this->assertSame(6, $sumRow['sum']);
        $this->assertSame(1, $sumRow['not_assigned']);

        $this->assertSame(1.0, $avgRow['emp_'.$employeeA->id]);
        $this->assertSame(2.0, $avgRow['emp_'.$employeeB->id]);
    }

    public function test_department_and_employee_filters_scope_the_employee_columns(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        $teamA = Team::factory()->create(['department_id' => $departmentA->id]);
        $teamB = Team::factory()->create(['department_id' => $departmentB->id]);

        $employeeA = Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'department_id' => $departmentA->id,
            'team_id' => $teamA->id,
        ]);
        $employeeB = Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'department_id' => $departmentB->id,
            'team_id' => $teamB->id,
        ]);

        $rows = Livewire::test(EmployeeDailyRequestReport::class)
            ->set('filters.department_id', $departmentA->id)
            ->instance()
            ->getTableRecords();

        $row = $rows->first();

        $this->assertArrayHasKey('emp_'.$employeeA->id, $row);
        $this->assertArrayNotHasKey('emp_'.$employeeB->id, $row);
    }

    public function test_it_opens_on_the_last_seven_days_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        Livewire::test(EmployeeDailyRequestReport::class)
            ->assertSet('filters.date_range', 'last_7_days')
            ->assertSet('filters.date_from', now()->subDays(6)->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->set('filters.date_range', 'yesterday')
            ->assertSet('filters.date_from', now()->subDay()->toDateString())
            ->assertSet('filters.date_to', now()->subDay()->toDateString());
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        Livewire::test(EmployeeDailyRequestReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_default_preset_lists_seven_days_plus_the_footer_rows(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        $rows = Livewire::test(EmployeeDailyRequestReport::class)
            ->instance()
            ->getTableRecords();

        $this->assertCount(9, $rows);
        $this->assertSame(now()->subDays(6)->toDateString().' ('.now()->subDays(6)->format('D').')', $rows->first()['day']);
    }

    public function test_all_preset_starts_at_the_first_request_and_ends_today(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        $firstDay = now()->subDays(20)->startOfDay();

        ServiceRequest::factory()->create(['assigned_to' => $employee->user_id])
            ->forceFill(['created_at' => $firstDay])->saveQuietly();

        $rows = Livewire::test(EmployeeDailyRequestReport::class)
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->instance()
            ->getTableRecords();

        $this->assertCount(21 + 2, $rows);
        $this->assertSame(1, $rows->firstWhere('day', __('admin.labels.sum_per_employee'))['emp_'.$employee->id]);
    }

    public function test_it_exports_csv_and_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_request_report.viewAny']);

        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        ServiceRequest::factory()->create(['assigned_to' => $employee->user_id]);

        Livewire::test(EmployeeDailyRequestReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(EmployeeDailyRequestReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }
}
