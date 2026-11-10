<?php

namespace Tests\Unit;

use App\Enums\AssignmentStrategy;
use App\Models\AssignmentRule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\AssignmentRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentRuleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_team_condition_matches_the_service_requests_team_department(): void
    {
        $salesDepartment = Department::factory()->create(['name' => 'Sales']);
        $supportDepartment = Department::factory()->create(['name' => 'Support']);

        $salesTeam = Team::factory()->create(['department_id' => $salesDepartment->id]);
        $supportTeam = Team::factory()->create(['department_id' => $supportDepartment->id]);

        $salesAgentUser = User::factory()->create();
        Employee::factory()->create([
            'user_id' => $salesAgentUser->id,
            'team_id' => $salesTeam->id,
            'is_active' => true,
        ]);

        AssignmentRule::query()->create([
            'name' => 'Sales leads — auto assign',
            'conditions' => [
                ['field' => 'owner_team', 'operator' => 'equals', 'value' => 'sales'],
            ],
            'employee_ids' => [Employee::query()->where('user_id', $salesAgentUser->id)->value('id')],
            'strategy' => AssignmentStrategy::LeastLoaded->value,
            'priority' => 1,
            'is_active' => true,
        ]);

        $salesRequest = ServiceRequest::factory()->create(['team_id' => $salesTeam->id, 'assigned_to' => null]);
        $supportRequest = ServiceRequest::factory()->create(['team_id' => $supportTeam->id, 'assigned_to' => null]);

        app(AssignmentRuleService::class)->apply($salesRequest);
        app(AssignmentRuleService::class)->apply($supportRequest);

        $this->assertSame($salesAgentUser->id, $salesRequest->refresh()->assigned_to);
        $this->assertNull($supportRequest->refresh()->assigned_to);
    }
}
