<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Models\CallEvaluation;
use App\Models\CallExclusion;
use App\Models\CallQualityCriterion;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\CallQualityService;
use App\Support\CallQuality\CallQualityLevels;
use App\Support\CallQuality\CallScore;
use Database\Seeders\CallQualitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CallQualityCoreTest extends TestCase
{
    use RefreshDatabase;

    private function seedCriteria(): void
    {
        $this->seed(CallQualitySeeder::class);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function service(): CallQualityService
    {
        return app(CallQualityService::class);
    }

    public function test_seeder_creates_the_reference_criteria_and_weights(): void
    {
        $this->seedCriteria();

        $criteria = CallQualityCriterion::query()->active()->ordered()->get();

        $this->assertSame(['c1', 'c2', 'c3', 'c4', 'c6', 'c5'], $criteria->pluck('key')->all());
        $this->assertSame(97.5, $criteria->sum('weight'));
        $this->assertSame('إغلاق البيعة', $criteria->firstWhere('key', 'c6')->getTranslation('name', 'ar'));
    }

    public function test_score_matches_the_reference_worked_example(): void
    {
        $this->seedCriteria();

        $result = CallScore::calculate(['c1' => 5, 'c2' => 4, 'c3' => 4, 'c4' => 5, 'c6' => 5, 'c5' => 5]);

        $this->assertEqualsWithDelta(91.79, $result['percent'], 0.01);
        $this->assertSame(28, $result['points']);
        $this->assertSame(30, $result['max_points']);
        $this->assertSame('excellent', $result['level']);
    }

    public function test_unscored_criteria_are_left_out_of_the_denominator(): void
    {
        $this->seedCriteria();

        $result = CallScore::calculate(['c1' => 3]);

        $this->assertEqualsWithDelta(60.0, $result['percent'], 0.001);
        $this->assertSame(1, $result['scored']);
        $this->assertSame('acceptable', $result['level']);
        $this->assertNull(CallScore::calculate([])['percent']);
        $this->assertNull(CallScore::calculate([])['level']);
    }

    public function test_levels_use_editable_thresholds(): void
    {
        $this->assertSame('very_good', CallQualityLevels::levelFor(85));
        $this->assertSame('needs_improvement', CallQualityLevels::levelFor(10));

        CallQualityLevels::save(['excellent' => 95, 'very_good' => 85]);

        $this->assertSame('very_good', CallQualityLevels::levelFor(92));
        $this->assertSame('good', CallQualityLevels::levelFor(84));
    }

    public function test_evaluation_stores_score_and_normalised_phone_and_rescores_when_weights_change(): void
    {
        $this->seedCriteria();

        $evaluation = CallEvaluation::factory()->create([
            'call_phone' => '0551234567',
            'scores' => ['c1' => 5, 'c2' => 1],
        ]);

        $this->assertSame('966551234567', $evaluation->call_phone);
        $this->assertEqualsWithDelta(((5 / 5 * 12.5) + (1 / 5 * 20)) / 32.5 * 100, $evaluation->score_percent, 0.01);

        CallQualityCriterion::query()->where('key', 'c2')->first()->update(['weight' => 12.5]);

        $this->assertEqualsWithDelta(60.0, $evaluation->refresh()->score_percent, 0.01);
        $this->assertSame('acceptable', $evaluation->level);
    }

    public function test_audit_queue_lists_the_first_lead_per_phone_and_skips_evaluated_and_excluded_phones(): void
    {
        $this->seedCriteria();
        $this->userWith(['call_evaluations.view_all']);
        $user = auth('admin')->user();

        $first = ServiceRequest::factory()->create(['phone' => '0551111111', 'created_at' => now()->subDays(2)]);
        ServiceRequest::factory()->create(['phone' => '966551111111', 'created_at' => now()->subDay()]);
        $evaluatedLead = ServiceRequest::factory()->create(['phone' => '0552222222', 'created_at' => now()->subDay()]);
        $excludedLead = ServiceRequest::factory()->create(['phone' => '0553333333', 'created_at' => now()->subDay()]);
        $returning = ServiceRequest::factory()->create(['phone' => '0554444444', 'created_at' => now()->subDay()]);
        ServiceRequest::factory()->create(['phone' => '0554444444', 'created_at' => now()->subDays(30)]);

        CallEvaluation::factory()->create(['call_phone' => $evaluatedLead->phone]);
        CallExclusion::factory()->create(['phone' => '+966 55 333 3333']);

        $queue = $this->service()->auditQueue(now()->subDays(6), now(), true, $user);

        $this->assertSame([$first->id], $queue->pluck('id')->all());

        $withReturning = $this->service()->auditQueue(now()->subDays(6), now(), false, $user)->pluck('id');
        $this->assertContains($returning->id, $withReturning->all());
        $this->assertNotContains($excludedLead->id, $withReturning->all());
    }

    public function test_exclude_takes_the_phone_out_of_the_queue_once(): void
    {
        $this->seedCriteria();
        $user = $this->userWith(['call_evaluations.view_all', 'call_exclusions.create']);
        $lead = ServiceRequest::factory()->create(['phone' => '0555555555', 'created_at' => now()->subDay()]);

        $this->service()->exclude($lead, $user);
        $this->service()->exclude($lead, $user);

        $this->assertSame(1, CallExclusion::query()->count());
        $this->assertSame('966555555555', CallExclusion::query()->value('phone'));
        $this->assertTrue($this->service()->auditQueue(now()->subDays(6), now(), false, $user)->isEmpty());
    }

    public function test_deleting_an_evaluation_puts_the_phone_back_in_the_queue(): void
    {
        $this->seedCriteria();
        $user = $this->userWith(['call_evaluations.view_all']);
        $lead = ServiceRequest::factory()->create(['phone' => '0556666666', 'created_at' => now()->subDay()]);
        $evaluation = CallEvaluation::factory()->create(['call_phone' => $lead->phone]);

        $this->assertTrue($this->service()->auditQueue(now()->subDays(6), now(), false, $user)->isEmpty());

        $evaluation->delete();

        $this->assertSame([$lead->id], $this->service()->auditQueue(now()->subDays(6), now(), false, $user)->pluck('id')->all());
    }

    public function test_agents_see_only_their_own_evaluations_and_cannot_edit(): void
    {
        $this->seedCriteria();
        $agent = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view']);
        $agentEmployee = Employee::factory()->create(['user_id' => $agent->id, 'role' => EmployeeRole::SalesPerson->value]);

        $own = CallEvaluation::factory()->create(['employee_id' => $agentEmployee->id]);
        $other = CallEvaluation::factory()->create();

        $visible = $this->service()->scopeEvaluations(CallEvaluation::query(), $agent)->pluck('id')->all();

        $this->assertSame([$own->id], $visible);
        $this->assertTrue($agent->can('view', $own));
        $this->assertFalse($agent->can('view', $other));
        $this->assertFalse($agent->can('update', $own));
    }

    public function test_team_lead_evaluates_and_discusses_team_but_only_approvers_approve(): void
    {
        $this->seedCriteria();
        $team = Team::factory()->create();
        $lead = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view', 'call_evaluations.create', 'call_evaluations.update', 'call_evaluations.discuss']);
        Employee::factory()->create(['user_id' => $lead->id, 'team_id' => $team->id, 'role' => EmployeeRole::TeamLead->value]);
        $member = Employee::factory()->create(['team_id' => $team->id]);
        $outsider = Employee::factory()->create();

        $mine = CallEvaluation::factory()->create(['employee_id' => $member->id, 'evaluator_id' => $lead->id]);
        $byOther = CallEvaluation::factory()->create(['employee_id' => $member->id]);
        $outside = CallEvaluation::factory()->create(['employee_id' => $outsider->id]);

        $this->assertTrue($this->service()->canEdit($lead, $mine));
        $this->assertFalse($this->service()->canEdit($lead, $byOther));
        $this->assertTrue($this->service()->canDiscuss($lead, $byOther));
        $this->assertFalse($this->service()->canDiscuss($lead, $outside));
        $this->assertFalse($this->service()->canApprove($lead));

        $manager = $this->userWith(['call_evaluations.approve']);
        $this->assertTrue($this->service()->canApprove($manager));

        $this->service()->setApproved($mine, true, $manager);
        $this->assertSame($manager->id, $mine->refresh()->approved_by);
        $this->assertNotNull($mine->approved_at);

        $this->service()->setApproved($mine, false, $manager);
        $this->assertNull($mine->refresh()->approved_at);
    }

    public function test_prefill_uses_the_latest_unrated_call_activity_of_the_lead(): void
    {
        $this->seedCriteria();
        $agentUser = User::factory()->create();
        $agent = Employee::factory()->create(['user_id' => $agentUser->id]);
        $lead = ServiceRequest::factory()->create(['phone' => '0557777777', 'assigned_to' => $agentUser->id]);

        $older = CrmActivity::factory()->create(['entity_type' => ServiceRequest::class, 'entity_id' => $lead->id, 'type' => 'call', 'employee_id' => $agent->id, 'created_at' => now()->subDays(2)]);
        $newer = CrmActivity::factory()->create(['entity_type' => ServiceRequest::class, 'entity_id' => $lead->id, 'type' => 'call', 'employee_id' => $agent->id, 'created_at' => now()->subDay()]);
        CrmActivity::factory()->create(['entity_type' => ServiceRequest::class, 'entity_id' => $lead->id, 'type' => 'email', 'created_at' => now()]);

        $prefill = $this->service()->prefillFor($lead);
        $this->assertSame($newer->id, $prefill['crm_activity_id']);
        $this->assertSame($agent->id, $prefill['employee_id']);

        CallEvaluation::factory()->create(['crm_activity_id' => $newer->id, 'service_request_id' => $lead->id]);

        $this->assertSame($older->id, $this->service()->prefillFor($lead)['crm_activity_id']);
    }

    public function test_average_score_respects_scope_and_range(): void
    {
        $this->seedCriteria();
        $viewer = $this->userWith(['call_evaluations.view_all']);

        CallEvaluation::factory()->scoredAll(5)->create(['call_date' => now()->toDateString()]);
        CallEvaluation::factory()->scoredAll(3)->create(['call_date' => now()->toDateString()]);
        CallEvaluation::factory()->scoredAll(1)->create(['call_date' => now()->subYear()->toDateString()]);

        $this->assertSame(80.0, $this->service()->averageScore(now()->startOfMonth(), now()->endOfMonth(), $viewer));
    }
}
