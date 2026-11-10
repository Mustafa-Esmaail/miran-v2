<?php

namespace Tests\Feature;

use App\Enums\CrmTaskStatus;
use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Filament\Admin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Admin\Resources\Companies\Pages\ListCompanies;
use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use App\Filament\Admin\Resources\CrmActivities\Pages\CreateCrmActivity;
use App\Filament\Admin\Resources\CrmActivities\Pages\ListCrmActivities;
use App\Filament\Admin\Resources\CrmTasks\CrmTaskResource;
use App\Filament\Admin\Resources\CrmTasks\Pages\CreateCrmTask;
use App\Filament\Admin\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Admin\Resources\SalesTargets\Pages\CreateSalesTarget;
use App\Filament\Admin\Resources\SalesTargets\Pages\ListSalesTargets;
use App\Filament\Admin\Resources\SalesTargets\SalesTargetResource;
use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\SalesTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmResourcesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'crm_tester', 'guard_name' => 'admin']);

        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }

        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function salesPersonFor(User $user): Employee
    {
        return Employee::factory()->create([
            'user_id' => $user->id,
            'role' => EmployeeRole::SalesPerson->value,
        ]);
    }

    public function test_resources_are_hidden_without_view_permission(): void
    {
        $this->actingAsAdminWithPermissions([]);

        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(CrmActivityResource::canViewAny());
        $this->assertFalse(CrmTaskResource::canViewAny());
        $this->assertFalse(SalesTargetResource::canViewAny());
    }

    public function test_company_list_shows_companies(): void
    {
        $this->actingAsAdminWithPermissions(['companies.viewAny']);
        $companies = Company::factory()->count(2)->create();

        Livewire::test(ListCompanies::class)
            ->assertOk()
            ->assertCanSeeTableRecords($companies);
    }

    public function test_company_can_be_created(): void
    {
        $this->actingAsAdminWithPermissions(['companies.viewAny', 'companies.create']);

        Livewire::test(CreateCompany::class)
            ->fillForm(['name' => 'Miran Training Co'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', ['name' => 'Miran Training Co']);
    }

    public function test_company_name_is_required(): void
    {
        $this->actingAsAdminWithPermissions(['companies.viewAny', 'companies.create']);

        Livewire::test(CreateCompany::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_sales_person_only_sees_companies_they_manage(): void
    {
        $admin = $this->actingAsAdminWithPermissions(['companies.viewAny']);
        $employee = $this->salesPersonFor($admin);

        $own = Company::factory()->create(['account_manager_id' => $employee->id]);
        $other = Company::factory()->create(['account_manager_id' => Employee::factory()->create()->id]);

        Livewire::test(ListCompanies::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_activity_list_shows_activities(): void
    {
        $this->actingAsAdminWithPermissions(['crm_activities.viewAny']);
        $activities = CrmActivity::factory()->count(2)->create();

        Livewire::test(ListCrmActivities::class)
            ->assertOk()
            ->assertCanSeeTableRecords($activities);
    }

    public function test_activity_can_be_logged_against_a_deal(): void
    {
        $this->actingAsAdminWithPermissions(['crm_activities.viewAny', 'crm_activities.create']);
        $deal = Deal::factory()->create();

        Livewire::test(CreateCrmActivity::class)
            ->fillForm([
                'entity_type' => Deal::class,
                'entity_id' => $deal->id,
                'type' => 'call',
                'subject' => 'Intro call',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('crm_activities', [
            'entity_type' => Deal::class,
            'entity_id' => $deal->id,
            'type' => 'call',
            'subject' => 'Intro call',
        ]);
    }

    public function test_sales_person_only_sees_their_own_activities(): void
    {
        $admin = $this->actingAsAdminWithPermissions(['crm_activities.viewAny']);
        $employee = $this->salesPersonFor($admin);

        $own = CrmActivity::factory()->create(['employee_id' => $employee->id]);
        $other = CrmActivity::factory()->create(['employee_id' => Employee::factory()->create()->id]);

        Livewire::test(ListCrmActivities::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_task_kanban_shows_tasks(): void
    {
        $this->actingAsAdminWithPermissions(['crm_tasks.viewAny']);
        CrmTask::factory()->create(['title' => 'Kanban task alpha']);
        CrmTask::factory()->done()->create(['title' => 'Kanban task omega']);

        Livewire::test(ListCrmTasks::class)
            ->assertOk()
            ->assertSee('Kanban task alpha')
            ->assertSee('Kanban task omega');
    }

    public function test_task_created_as_done_is_marked_complete(): void
    {
        $this->actingAsAdminWithPermissions(['crm_tasks.viewAny', 'crm_tasks.create']);
        $deal = Deal::factory()->create();

        Livewire::test(CreateCrmTask::class)
            ->fillForm([
                'title' => 'Send proposal',
                'entity_type' => Deal::class,
                'entity_id' => $deal->id,
                'priority' => 'high',
                'status' => CrmTaskStatus::Done->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = CrmTask::query()->where('title', 'Send proposal')->firstOrFail();

        $this->assertTrue($task->is_done);
        $this->assertNotNull($task->completed_at);
    }

    public function test_reopening_a_task_clears_its_completion(): void
    {
        $task = CrmTask::factory()->done()->create();
        $this->assertTrue($task->is_done);

        $task->update(['status' => CrmTaskStatus::InProgress]);

        $this->assertFalse($task->refresh()->is_done);
        $this->assertNull($task->completed_at);
    }

    public function test_sales_person_only_sees_their_own_tasks(): void
    {
        $admin = $this->actingAsAdminWithPermissions(['crm_tasks.viewAny']);
        $employee = $this->salesPersonFor($admin);

        CrmTask::factory()->create(['employee_id' => $employee->id, 'title' => 'My own follow-up']);
        CrmTask::factory()->create(['employee_id' => Employee::factory()->create()->id, 'title' => 'Colleague follow-up']);

        Livewire::test(ListCrmTasks::class)
            ->assertSee('My own follow-up')
            ->assertDontSee('Colleague follow-up');
    }

    public function test_sales_target_list_shows_targets(): void
    {
        $this->actingAsAdminWithPermissions(['sales_targets.viewAny']);
        $targets = SalesTarget::factory()->count(2)->create();

        Livewire::test(ListSalesTargets::class)
            ->assertOk()
            ->assertCanSeeTableRecords($targets);
    }

    public function test_sales_target_can_be_created(): void
    {
        $this->actingAsAdminWithPermissions(['sales_targets.viewAny', 'sales_targets.create']);
        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(CreateSalesTarget::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'period_type' => 'monthly',
                'period' => '2026-11',
                'target_revenue' => 75000,
                'target_leads' => 20,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sales_targets', [
            'employee_id' => $employee->id,
            'period' => '2026-11',
            'target_leads' => 20,
        ]);
    }

    public function test_sales_target_requires_employee_and_period(): void
    {
        $this->actingAsAdminWithPermissions(['sales_targets.viewAny', 'sales_targets.create']);

        Livewire::test(CreateSalesTarget::class)
            ->fillForm(['employee_id' => null, 'period' => ''])
            ->call('create')
            ->assertHasFormErrors(['employee_id' => 'required', 'period' => 'required']);
    }

    public function test_sales_person_only_sees_their_own_targets(): void
    {
        $admin = $this->actingAsAdminWithPermissions(['sales_targets.viewAny']);
        $employee = $this->salesPersonFor($admin);

        $own = SalesTarget::factory()->create(['employee_id' => $employee->id]);
        $other = SalesTarget::factory()->create();

        Livewire::test(ListSalesTargets::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
