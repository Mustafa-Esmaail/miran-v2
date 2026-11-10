<?php

namespace Tests\Unit;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_drops_course_group_conversion_and_sales_owner_fields(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        foreach (['viewAny', 'view', 'update'] as $action) {
            Permission::query()->create(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $admin->assignRole($role);
        $admin->givePermissionTo(['service_requests.viewAny', 'service_requests.view', 'service_requests.update']);

        $serviceRequest = ServiceRequest::factory()->create();

        Livewire::test(EditServiceRequest::class, ['record' => $serviceRequest->getRouteKey()])
            ->assertOk()
            ->assertSee('Assigned to')
            ->assertDontSee('Course group')
            ->assertDontSee('Converted order')
            ->assertDontSee('Sheets sync')
            ->assertDontSee('Sales owner');
    }

    public function test_sales_owner_id_column_no_longer_exists(): void
    {
        $this->assertFalse(Schema::hasColumn('service_requests', 'sales_owner_id'));
    }

    public function test_assigned_to_options_are_filtered_by_the_selected_team(): void
    {
        $salesDepartment = Department::factory()->create(['name' => 'Sales']);
        $supportDepartment = Department::factory()->create(['name' => 'Support']);

        $salesTeam = Team::factory()->create(['department_id' => $salesDepartment->id]);
        $supportTeam = Team::factory()->create(['department_id' => $supportDepartment->id]);

        $salesUser = User::factory()->create(['name' => 'Sara Sales']);
        $supportUser = User::factory()->create(['name' => 'Sam Support']);

        Employee::factory()->create(['user_id' => $salesUser->id, 'team_id' => $salesTeam->id]);
        Employee::factory()->create(['user_id' => $supportUser->id, 'team_id' => $supportTeam->id]);

        $assignedToOptions = new ReflectionMethod(ServiceRequestForm::class, 'assignedToOptions');
        $assignedToOptions->setAccessible(true);

        $supportOptions = $assignedToOptions->invoke(null, $supportTeam->id);
        $this->assertArrayHasKey($supportUser->id, $supportOptions);
        $this->assertArrayNotHasKey($salesUser->id, $supportOptions);

        $salesOptions = $assignedToOptions->invoke(null, $salesTeam->id);
        $this->assertArrayHasKey($salesUser->id, $salesOptions);
        $this->assertArrayNotHasKey($supportUser->id, $salesOptions);
    }

    public function test_assigned_to_options_include_every_employee_when_no_team_is_selected(): void
    {
        $salesTeam = Team::factory()->create();
        $supportTeam = Team::factory()->create();

        $salesUser = User::factory()->create(['name' => 'Sara Sales']);
        $supportUser = User::factory()->create(['name' => 'Sam Support']);

        Employee::factory()->create(['user_id' => $salesUser->id, 'team_id' => $salesTeam->id]);
        Employee::factory()->create(['user_id' => $supportUser->id, 'team_id' => $supportTeam->id]);

        $assignedToOptions = new ReflectionMethod(ServiceRequestForm::class, 'assignedToOptions');
        $assignedToOptions->setAccessible(true);

        $options = $assignedToOptions->invoke(null, null);
        $this->assertArrayHasKey($salesUser->id, $options);
        $this->assertArrayHasKey($supportUser->id, $options);
    }

    public function test_sales_agents_cannot_see_source_type_assignment_or_security_fields(): void
    {
        $salesAgentUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        $this->actingAs($salesAgentUser, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        foreach (['viewAny', 'view', 'update'] as $action) {
            Permission::query()->create(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $salesAgentUser->assignRole($role);
        $salesAgentUser->givePermissionTo(['service_requests.viewAny', 'service_requests.view', 'service_requests.update']);

        $serviceRequest = ServiceRequest::factory()->create(['assigned_to' => $salesAgentUser->id]);

        Livewire::test(EditServiceRequest::class, ['record' => $serviceRequest->getRouteKey()])
            ->assertOk()
            ->assertDontSee('Source')
            ->assertDontSee('Type')
            ->assertDontSee('Assignment')
            ->assertDontSee('Security');
    }

    public function test_status_options_are_scoped_to_the_selected_type(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        foreach (['viewAny', 'create'] as $action) {
            Permission::query()->create(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $admin->assignRole($role);
        $admin->givePermissionTo(['service_requests.viewAny', 'service_requests.create']);

        $complaintsType = ServiceRequestType::query()->create([
            'name' => ['ar' => 'شكاوى'],
            'slug' => 'complaints_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $leadsType = ServiceRequestType::query()->create([
            'name' => ['ar' => 'طلبات'],
            'slug' => 'leads_type',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $complaintsStage = ServiceRequestStage::query()->create([
            'type_id' => $complaintsType->id,
            'name' => ['ar' => 'مرحلة الشكاوى'],
            'slug' => 'complaints_stage',
            'color' => '#ef4444',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $leadsStage = ServiceRequestStage::query()->create([
            'type_id' => $leadsType->id,
            'name' => ['ar' => 'مرحلة العملاء المحتملين'],
            'slug' => 'leads_stage',
            'color' => '#3b82f6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'حالة الشكاوى فقط'],
            'slug' => 'complaint_only_status',
            'color' => '#ef4444',
            'outcome' => 'open',
            'stage_id' => $complaintsStage->id,
            'is_active' => true,
            'sort_order' => 100,
        ]);

        ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'حالة العملاء المحتملين فقط'],
            'slug' => 'lead_only_status',
            'color' => '#3b82f6',
            'outcome' => 'open',
            'stage_id' => $leadsStage->id,
            'is_active' => true,
            'sort_order' => 101,
        ]);

        Livewire::test(CreateServiceRequest::class)
            ->fillForm(['type_id' => $complaintsType->id])
            ->assertSee('حالة الشكاوى فقط')
            ->assertDontSee('حالة العملاء المحتملين فقط');

        Livewire::test(CreateServiceRequest::class)
            ->fillForm(['type_id' => $leadsType->id])
            ->assertSee('حالة العملاء المحتملين فقط')
            ->assertDontSee('حالة الشكاوى فقط');
    }
}
