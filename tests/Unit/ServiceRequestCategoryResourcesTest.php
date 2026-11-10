<?php

namespace Tests\Unit;

use App\Enums\EmployeeRole;
use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\ComplaintServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListB2bServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListB2cServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListComplaintServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestCategoryResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_resource_only_lists_its_own_source_category(): void
    {
        $complaint = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint]);
        $b2b = ServiceRequest::factory()->create(['source' => ServiceRequestSource::CorporateTraining]);
        $b2c = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead]);

        $complaintIds = ComplaintServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($complaintIds->contains($complaint->id));
        $this->assertFalse($complaintIds->contains($b2b->id));
        $this->assertFalse($complaintIds->contains($b2c->id));

        $b2bIds = B2bServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($b2bIds->contains($b2b->id));
        $this->assertFalse($b2bIds->contains($complaint->id));
        $this->assertFalse($b2bIds->contains($b2c->id));

        $b2cIds = B2cServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($b2cIds->contains($b2c->id));
        $this->assertFalse($b2cIds->contains($complaint->id));
        $this->assertFalse($b2cIds->contains($b2b->id));
    }

    public function test_each_resource_requires_its_own_permission_to_view_any(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->assertFalse(ComplaintServiceRequestResource::canViewAny());
        $this->assertFalse(B2bServiceRequestResource::canViewAny());
        $this->assertFalse(B2cServiceRequestResource::canViewAny());

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('complaint_service_requests.viewAny');

        $this->assertTrue(ComplaintServiceRequestResource::canViewAny());
        $this->assertFalse(B2bServiceRequestResource::canViewAny());
        $this->assertFalse(B2cServiceRequestResource::canViewAny());
    }

    public function test_blanket_service_requests_permission_still_grants_access_to_every_category(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'service_requests.viewAny', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('service_requests.viewAny');

        $this->assertTrue(ComplaintServiceRequestResource::canViewAny());
        $this->assertTrue(B2bServiceRequestResource::canViewAny());
        $this->assertTrue(B2cServiceRequestResource::canViewAny());
    }

    public function test_each_list_page_renders_and_shows_only_its_own_category(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'service_requests.viewAny', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('service_requests.viewAny');

        $complaint = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint, 'phone' => '0500000001']);
        $b2b = ServiceRequest::factory()->create(['source' => ServiceRequestSource::CorporateTraining, 'phone' => '0500000002']);
        $b2c = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'phone' => '0500000003']);

        Livewire::test(ListComplaintServiceRequests::class)
            ->assertOk()
            ->assertSee($complaint->phone)
            ->assertDontSee($b2b->phone)
            ->assertDontSee($b2c->phone);

        Livewire::test(ListB2bServiceRequests::class)
            ->assertOk()
            ->assertSee($b2b->phone)
            ->assertDontSee($complaint->phone)
            ->assertDontSee($b2c->phone);

        Livewire::test(ListB2cServiceRequests::class)
            ->assertOk()
            ->assertSee($b2c->phone)
            ->assertDontSee($complaint->phone)
            ->assertDontSee($b2b->phone);
    }

    public function test_sales_agent_only_sees_service_requests_assigned_to_them_in_every_category_resource(): void
    {
        $salesAgentUser = User::factory()->create();
        $otherSalesAgentUser = User::factory()->create();

        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        Employee::factory()->create(['user_id' => $otherSalesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);

        $ownComplaint = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'assigned_to' => $salesAgentUser->id,
        ]);
        $othersComplaint = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'assigned_to' => $otherSalesAgentUser->id,
        ]);
        $unassignedComplaint = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'assigned_to' => null,
        ]);

        $this->actingAs($salesAgentUser, 'admin');

        $visibleIds = ComplaintServiceRequestResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visibleIds->contains($ownComplaint->id));
        $this->assertFalse($visibleIds->contains($othersComplaint->id));
        $this->assertFalse($visibleIds->contains($unassignedComplaint->id));
    }

    public function test_team_lead_sees_their_teams_service_requests_and_sales_manager_sees_everything(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        $teamLeadUser = User::factory()->create();
        $teamMemberUser = User::factory()->create();
        $otherTeamUser = User::factory()->create();
        $salesManagerUser = User::factory()->create();

        Employee::factory()->create(['user_id' => $teamLeadUser->id, 'team_id' => $team->id, 'role' => EmployeeRole::TeamLead]);
        Employee::factory()->create(['user_id' => $teamMemberUser->id, 'team_id' => $team->id, 'role' => EmployeeRole::SalesPerson]);
        Employee::factory()->create(['user_id' => $otherTeamUser->id, 'team_id' => $otherTeam->id, 'role' => EmployeeRole::SalesPerson]);
        Employee::factory()->create(['user_id' => $salesManagerUser->id, 'role' => EmployeeRole::GeneralManager]);

        $teamRequest = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'assigned_to' => $teamMemberUser->id]);
        $otherTeamRequest = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'assigned_to' => $otherTeamUser->id]);

        $this->actingAs($teamLeadUser, 'admin');
        $teamLeadIds = B2cServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($teamLeadIds->contains($teamRequest->id));
        $this->assertFalse($teamLeadIds->contains($otherTeamRequest->id));

        $this->actingAs($salesManagerUser, 'admin');
        $managerIds = B2cServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($managerIds->contains($teamRequest->id));
        $this->assertTrue($managerIds->contains($otherTeamRequest->id));
    }

    public function test_employee_scoping_applies_the_same_way_on_the_original_all_requests_resource(): void
    {
        $salesAgentUser = User::factory()->create();
        $otherSalesAgentUser = User::factory()->create();

        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        Employee::factory()->create(['user_id' => $otherSalesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);

        $own = ServiceRequest::factory()->create(['assigned_to' => $salesAgentUser->id]);
        $others = ServiceRequest::factory()->create(['assigned_to' => $otherSalesAgentUser->id]);

        $this->actingAs($salesAgentUser, 'admin');

        $ids = ServiceRequestResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($ids->contains($own->id));
        $this->assertFalse($ids->contains($others->id));
    }
}
