<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\CallEvaluations\Pages\CreateCallEvaluation;
use App\Filament\Admin\Resources\CallEvaluations\Pages\EditCallEvaluation;
use App\Filament\Admin\Resources\CallEvaluations\Pages\ListCallEvaluations;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Models\CallEvaluation;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\CallQualitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CallEvaluationResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The reference worked example: 91.79 %, excellent.
     *
     * @var array<string, int>
     */
    private const REFERENCE_SCORES = ['c1' => 5, 'c2' => 4, 'c3' => 4, 'c4' => 5, 'c6' => 5, 'c5' => 5];

    protected function setUp(): void
    {
        parent::setUp();

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

    private function salesPerson(?User $user = null): Employee
    {
        return Employee::factory()->create([
            'user_id' => $user?->getKey() ?? User::factory()->create()->getKey(),
            'role' => EmployeeRole::SalesPerson->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function callActivity(ServiceRequest $lead, Employee $employee, array $attributes = []): CrmActivity
    {
        return CrmActivity::factory()->create([
            'entity_type' => ServiceRequest::class,
            'entity_id' => $lead->getKey(),
            'type' => 'call',
            'employee_id' => $employee->getKey(),
            ...$attributes,
        ]);
    }

    public function test_agent_only_sees_their_own_evaluations(): void
    {
        $agentUser = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view']);
        $agent = $this->salesPerson($agentUser);

        $own = CallEvaluation::factory()->create(['employee_id' => $agent->getKey()]);
        $other = CallEvaluation::factory()->create(['employee_id' => $this->salesPerson()->getKey()]);

        Livewire::test(ListCallEvaluations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_view_all_user_sees_every_evaluation(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view_all']);

        $evaluations = CallEvaluation::factory()->count(2)->create();

        Livewire::test(ListCallEvaluations::class)
            ->assertCanSeeTableRecords($evaluations);
    }

    public function test_create_saves_scores_notes_evaluator_and_computes_percent_and_level(): void
    {
        $user = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.create', 'call_evaluations.view_all']);
        $employee = $this->salesPerson();

        Livewire::test(CreateCallEvaluation::class)
            ->fillForm([
                'employee_id' => $employee->getKey(),
                'call_date' => '2026-11-05',
                'call_phone' => '0551234567',
                'scores' => self::REFERENCE_SCORES,
                'notes' => ['c1' => 'Strong opening', 'c2' => '   '],
                'general_notes' => 'Good call overall',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $evaluation = CallEvaluation::query()->sole();

        $this->assertSame($user->getKey(), $evaluation->evaluator_id);
        $this->assertSame($employee->getKey(), $evaluation->employee_id);
        $this->assertEquals(self::REFERENCE_SCORES, $evaluation->scores);
        $this->assertEquals(['c1' => 'Strong opening'], $evaluation->notes);
        $this->assertSame('966551234567', $evaluation->call_phone);
        $this->assertEqualsWithDelta(91.79, $evaluation->score_percent, 0.01);
        $this->assertSame('excellent', $evaluation->level);
        $this->assertSame('Good call overall', $evaluation->general_notes);
    }

    public function test_create_requires_every_criterion_to_be_scored(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.create', 'call_evaluations.view_all']);

        Livewire::test(CreateCallEvaluation::class)
            ->fillForm([
                'employee_id' => $this->salesPerson()->getKey(),
                'call_date' => '2026-11-05',
                'call_phone' => '0551234567',
                'scores' => ['c1' => 5],
            ])
            ->call('create')
            ->assertHasFormErrors(['scores.c2' => 'required', 'scores.c5' => 'required']);

        $this->assertDatabaseCount('call_evaluations', 0);
    }

    public function test_employee_options_exclude_own_employee_without_view_all(): void
    {
        $user = $this->userWith(['call_evaluations.create']);
        $own = Employee::factory()->create(['user_id' => $user->getKey()]);
        $other = Employee::factory()->create();

        $options = CallEvaluationForm::employeeOptions();

        $this->assertArrayNotHasKey($own->getKey(), $options);
        $this->assertArrayHasKey($other->getKey(), $options);
    }

    public function test_create_is_prefilled_from_lead_and_activity_query_string(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.create', 'call_evaluations.view_all']);
        $agent = $this->salesPerson();
        $lead = ServiceRequest::factory()->create(['phone' => '0559998888', 'assigned_to' => $agent->user_id]);
        $activity = $this->callActivity($lead, $agent, ['completed_at' => '2026-11-03 10:00:00', 'created_at' => '2026-11-03 09:00:00']);
        $this->callActivity($lead, $agent, ['completed_at' => '2026-11-04 10:00:00', 'created_at' => '2026-11-04 09:00:00']);

        Livewire::withQueryParams(['service_request' => $lead->getKey(), 'crm_activity' => $activity->getKey()])
            ->test(CreateCallEvaluation::class)
            ->assertOk()
            ->assertFormSet([
                'service_request_id' => $lead->getKey(),
                'crm_activity_id' => $activity->getKey(),
                'employee_id' => $agent->getKey(),
                'call_phone' => '0559998888',
                'call_date' => '2026-11-03',
            ]);
    }

    public function test_prefill_is_forbidden_when_the_user_cannot_see_the_leads_employee(): void
    {
        $user = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.create']);
        $this->salesPerson($user);
        $otherAgent = $this->salesPerson();
        $lead = ServiceRequest::factory()->create(['assigned_to' => $otherAgent->user_id]);
        $activity = $this->callActivity($lead, $otherAgent);

        Livewire::withQueryParams(['service_request' => $lead->getKey(), 'crm_activity' => $activity->getKey()])
            ->test(CreateCallEvaluation::class)
            ->assertForbidden();
    }

    public function test_activity_options_exclude_already_rated_activities(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.create', 'call_evaluations.view_all']);
        $agent = $this->salesPerson();
        $lead = ServiceRequest::factory()->create(['assigned_to' => $agent->user_id]);
        $rated = $this->callActivity($lead, $agent);
        $unrated = $this->callActivity($lead, $agent);
        $this->callActivity($lead, $agent, ['type' => 'email']);

        $ratedEvaluation = CallEvaluation::factory()->create([
            'employee_id' => $agent->getKey(),
            'service_request_id' => $lead->getKey(),
            'crm_activity_id' => $rated->getKey(),
        ]);

        $this->assertSame([$unrated->getKey()], array_keys(CallEvaluationForm::activityOptions($lead->getKey())));
        $this->assertEqualsCanonicalizing(
            [$rated->getKey(), $unrated->getKey()],
            array_keys(CallEvaluationForm::activityOptions($lead->getKey(), $ratedEvaluation->getKey())),
        );

        Livewire::test(CreateCallEvaluation::class)
            ->fillForm([
                'employee_id' => $agent->getKey(),
                'call_date' => '2026-11-05',
                'call_phone' => '0551234567',
                'service_request_id' => $lead->getKey(),
                'crm_activity_id' => $rated->getKey(),
                'scores' => self::REFERENCE_SCORES,
            ])
            ->call('create')
            ->assertHasFormErrors(['crm_activity_id']);
    }

    public function test_discussed_and_approved_toggles_stamp_who_and_when(): void
    {
        $user = $this->userWith([
            'call_evaluations.viewAny',
            'call_evaluations.view',
            'call_evaluations.update',
            'call_evaluations.discuss',
            'call_evaluations.approve',
            'call_evaluations.view_all',
        ]);
        $evaluation = CallEvaluation::factory()->scoredAll(4)->create(['evaluator_id' => $user->getKey()]);

        Livewire::test(EditCallEvaluation::class, ['record' => $evaluation->getRouteKey()])
            ->assertFormFieldIsVisible('is_discussed')
            ->assertFormFieldIsVisible('is_approved')
            ->fillForm(['is_discussed' => true, 'is_approved' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $evaluation->refresh();

        $this->assertSame($user->getKey(), $evaluation->discussed_by);
        $this->assertNotNull($evaluation->discussed_at);
        $this->assertSame($user->getKey(), $evaluation->approved_by);
        $this->assertNotNull($evaluation->approved_at);
    }

    public function test_discuss_and_approve_toggles_are_hidden_without_rights(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view', 'call_evaluations.update', 'call_evaluations.view_all']);
        $evaluation = CallEvaluation::factory()->scoredAll(4)->create();

        Livewire::test(EditCallEvaluation::class, ['record' => $evaluation->getRouteKey()])
            ->assertFormFieldIsHidden('is_discussed')
            ->assertFormFieldIsHidden('is_approved');
    }

    public function test_table_actions_mark_discussed_and_approve(): void
    {
        $user = $this->userWith([
            'call_evaluations.viewAny',
            'call_evaluations.discuss',
            'call_evaluations.approve',
            'call_evaluations.view_all',
        ]);
        $evaluation = CallEvaluation::factory()->create();

        Livewire::test(ListCallEvaluations::class)
            ->assertTableActionHidden('undo_discussed', $evaluation)
            ->callTableAction('mark_discussed', $evaluation)
            ->callTableAction('approve', $evaluation);

        $evaluation->refresh();

        $this->assertSame($user->getKey(), $evaluation->discussed_by);
        $this->assertSame($user->getKey(), $evaluation->approved_by);
    }

    public function test_table_review_actions_are_hidden_without_rights(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view_all']);
        $evaluation = CallEvaluation::factory()->create();

        Livewire::test(ListCallEvaluations::class)
            ->assertTableActionHidden('mark_discussed', $evaluation)
            ->assertTableActionHidden('approve', $evaluation);
    }

    public function test_edit_is_denied_to_non_evaluator_without_view_all(): void
    {
        $user = $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view', 'call_evaluations.update']);
        $theirs = CallEvaluation::factory()->create(['evaluator_id' => $user->getKey()]);
        $someoneElses = CallEvaluation::factory()->create();

        $this->assertTrue(CallEvaluationResource::canEdit($theirs));
        $this->assertFalse(CallEvaluationResource::canEdit($someoneElses));

        Livewire::test(ListCallEvaluations::class)
            ->assertTableActionVisible('edit', $theirs)
            ->assertTableActionHidden('edit', $someoneElses);
    }

    public function test_csv_export_downloads_the_filtered_rows(): void
    {
        $this->userWith(['call_evaluations.viewAny', 'call_evaluations.view_all']);
        CallEvaluation::factory()->count(2)->create();

        Livewire::test(ListCallEvaluations::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();
    }
}
