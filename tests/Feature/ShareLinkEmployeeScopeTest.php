<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\ShareLinks\Pages\CreateShareLink;
use App\Filament\Admin\Resources\ShareLinks\Pages\ListShareLinks;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ShareLink;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ShareLinkEmployeeScopeTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Team $team;

    private Team $otherTeam;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['share_links.viewAny', 'share_links.view', 'share_links.create', 'employees.viewAny'] as $name) {
            Permission::query()->create(['name' => $name, 'guard_name' => 'admin']);
        }

        $this->department = Department::factory()->create();
        $this->team = Team::factory()->create(['department_id' => $this->department->id]);
        $this->otherTeam = Team::factory()->create(['department_id' => $this->department->id]);
    }

    public function test_sales_agent_can_only_pick_themselves_and_the_field_is_locked(): void
    {
        $agent = $this->makeEmployee('AGT-1', EmployeeRole::SalesPerson, $this->team);
        $teammate = $this->makeEmployee('AGT-2', EmployeeRole::SalesPerson, $this->team);
        $this->signIn($agent);

        $component = Livewire::test(CreateShareLink::class)
            ->assertOk()
            ->assertSchemaStateSet(['employee_id' => $agent->id])
            ->assertFormFieldDisabled('employee_id');

        $this->assertSame([$agent->id], array_keys($this->employeeOptions($component)));
        $this->assertArrayNotHasKey($teammate->id, $this->employeeOptions($component));
    }

    public function test_sales_agent_creates_link_for_themselves_by_default(): void
    {
        $agent = $this->makeEmployee('AGT-1', EmployeeRole::SalesPerson, $this->team);
        $this->signIn($agent);

        Livewire::test(CreateShareLink::class)
            ->fillForm(['target_type' => 'homepage'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('share_links', ['employee_id' => $agent->id, 'target_type' => 'homepage']);
    }

    public function test_sales_agent_cannot_submit_a_link_for_someone_else(): void
    {
        $agent = $this->makeEmployee('AGT-1', EmployeeRole::SalesPerson, $this->team);
        $teammate = $this->makeEmployee('AGT-2', EmployeeRole::SalesPerson, $this->team);
        $this->signIn($agent);

        Livewire::test(CreateShareLink::class)
            ->set('data.employee_id', $teammate->id)
            ->fillForm(['target_type' => 'homepage'])
            ->call('create')
            ->assertHasFormErrors(['employee_id']);

        $this->assertDatabaseCount('share_links', 0);
    }

    public function test_sales_agent_only_sees_their_own_links(): void
    {
        $agent = $this->makeEmployee('AGT-1', EmployeeRole::SalesPerson, $this->team);
        $teammate = $this->makeEmployee('AGT-2', EmployeeRole::SalesPerson, $this->team);
        $own = $this->makeLink($agent);
        $theirs = $this->makeLink($teammate);
        $this->signIn($agent);

        Livewire::test(ListShareLinks::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_team_lead_can_pick_and_see_their_team_only(): void
    {
        $lead = $this->makeEmployee('LEAD-1', EmployeeRole::TeamLead, $this->team);
        $member = $this->makeEmployee('AGT-1', EmployeeRole::SalesPerson, $this->team);
        $outsider = $this->makeEmployee('AGT-9', EmployeeRole::SalesPerson, $this->otherTeam);
        $memberLink = $this->makeLink($member);
        $outsiderLink = $this->makeLink($outsider);
        $this->signIn($lead);

        $component = Livewire::test(CreateShareLink::class)
            ->assertOk()
            ->assertFormFieldEnabled('employee_id');

        $options = array_keys($this->employeeOptions($component));
        sort($options);
        $this->assertSame([$lead->id, $member->id], $options);

        $component
            ->fillForm(['employee_id' => $member->id, 'target_type' => 'homepage'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('share_links', ['employee_id' => $member->id, 'target_type' => 'homepage']);

        Livewire::test(ListShareLinks::class)
            ->assertCanSeeTableRecords([$memberLink])
            ->assertCanNotSeeTableRecords([$outsiderLink]);
    }

    public function test_team_lead_cannot_create_a_link_for_another_team(): void
    {
        $lead = $this->makeEmployee('LEAD-1', EmployeeRole::TeamLead, $this->team);
        $outsider = $this->makeEmployee('AGT-9', EmployeeRole::SalesPerson, $this->otherTeam);
        $this->signIn($lead);

        Livewire::test(CreateShareLink::class)
            ->fillForm(['employee_id' => $outsider->id, 'target_type' => 'homepage'])
            ->call('create')
            ->assertHasFormErrors(['employee_id']);

        $this->assertDatabaseCount('share_links', 0);
    }

    public function test_general_manager_sees_every_employee_and_link(): void
    {
        $manager = $this->makeEmployee('GM-1', EmployeeRole::GeneralManager, $this->team);
        $outsider = $this->makeEmployee('AGT-9', EmployeeRole::SalesPerson, $this->otherTeam);
        $outsiderLink = $this->makeLink($outsider);
        $this->signIn($manager);

        $component = Livewire::test(CreateShareLink::class)->assertFormFieldEnabled('employee_id');
        $this->assertArrayHasKey($outsider->id, $this->employeeOptions($component));

        Livewire::test(ListShareLinks::class)->assertCanSeeTableRecords([$outsiderLink]);
    }

    public function test_admin_without_employee_record_keeps_full_access(): void
    {
        $outsider = $this->makeEmployee('AGT-9', EmployeeRole::SalesPerson, $this->otherTeam);
        $outsiderLink = $this->makeLink($outsider);
        $this->signIn(null);

        $component = Livewire::test(CreateShareLink::class)->assertFormFieldEnabled('employee_id');
        $this->assertArrayHasKey($outsider->id, $this->employeeOptions($component));

        Livewire::test(ListShareLinks::class)->assertCanSeeTableRecords([$outsiderLink]);
    }

    public function test_user_without_employees_view_any_is_limited_to_themselves(): void
    {
        $employee = $this->makeEmployee('EMP-1', null, $this->team);
        $other = $this->makeEmployee('EMP-2', null, $this->team);
        $otherLink = $this->makeLink($other);
        $this->signIn($employee, withEmployeesViewAny: false);

        $component = Livewire::test(CreateShareLink::class)->assertOk();
        $this->assertSame([$employee->id], array_keys($this->employeeOptions($component)));

        Livewire::test(ListShareLinks::class)->assertCanNotSeeTableRecords([$otherLink]);
    }

    private function makeEmployee(string $code, ?EmployeeRole $role, Team $team): Employee
    {
        return Employee::factory()->create([
            'user_id' => User::factory()->create()->id,
            'employee_code' => $code,
            'role' => $role,
            'department_id' => $team->department_id,
            'team_id' => $team->id,
            'is_active' => true,
        ]);
    }

    private function makeLink(Employee $employee): ShareLink
    {
        return ShareLink::query()->create([
            'employee_id' => $employee->id,
            'target_type' => 'homepage',
        ]);
    }

    private function signIn(?Employee $employee, bool $withEmployeesViewAny = true): void
    {
        $user = $employee?->user_id ? User::query()->findOrFail($employee->user_id) : User::factory()->create();

        $this->actingAs($user, 'admin');

        $user->givePermissionTo(array_filter([
            'share_links.viewAny',
            'share_links.view',
            'share_links.create',
            $withEmployeesViewAny ? 'employees.viewAny' : null,
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function employeeOptions(Testable $component): array
    {
        /** @var Select $field */
        $field = $component->instance()->form->getComponent('employee_id');

        return $field->getOptions();
    }
}
