<?php

namespace Tests\Unit;

use App\Enums\CrmTaskStatus;
use App\Enums\DealStage;
use App\Enums\WorkflowTrigger;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use App\Models\WorkflowRule;
use App\Services\WorkflowRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRuleServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     * @param  array<int, array<string, mixed>>  $actions
     */
    private function makeRule(WorkflowTrigger $trigger, array $conditions, array $actions, bool $isActive = true): WorkflowRule
    {
        return WorkflowRule::query()->create([
            'name' => 'Rule '.fake()->unique()->word(),
            'trigger_event' => $trigger->value,
            'conditions' => $conditions,
            'actions' => $actions,
            'is_active' => $isActive,
        ]);
    }

    private function service(): WorkflowRuleService
    {
        return app(WorkflowRuleService::class);
    }

    public function test_create_task_action_assigns_the_task_to_the_leads_employee(): void
    {
        $agent = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $agent->id]);
        $lead = ServiceRequest::factory()->create(['source' => 'whatsapp', 'assigned_to' => $agent->id]);
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::LeadCreated, [
            ['field' => 'source', 'operator' => 'equals', 'value' => 'whatsapp'],
        ], [
            ['type' => 'create_task', 'title' => 'Call back within the hour', 'priority' => 'high', 'due_in_hours' => 1],
        ]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $task = CrmTask::query()->sole();
        $this->assertSame('Call back within the hour', $task->title);
        $this->assertSame(ServiceRequest::class, $task->entity_type);
        $this->assertSame($lead->id, $task->entity_id);
        $this->assertSame($employee->id, $task->employee_id);
        $this->assertSame(CrmTaskStatus::Todo, $task->status);
        $this->assertTrue($task->due_at->between(now()->addMinutes(59), now()->addMinutes(61)));
    }

    public function test_rule_is_skipped_when_a_condition_does_not_match(): void
    {
        $lead = ServiceRequest::factory()->create(['source' => 'form']);
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::LeadCreated, [
            ['field' => 'source', 'operator' => 'equals', 'value' => 'whatsapp'],
        ], [
            ['type' => 'create_task', 'title' => 'Should not exist'],
        ]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $this->assertDatabaseMissing('crm_tasks', ['title' => 'Should not exist']);
    }

    public function test_not_equals_operator_matches_other_values(): void
    {
        $lead = ServiceRequest::factory()->create(['source' => 'form']);
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::LeadCreated, [
            ['field' => 'source', 'operator' => 'not_equals', 'value' => 'whatsapp'],
        ], [
            ['type' => 'create_task', 'title' => 'Non-WhatsApp follow-up'],
        ]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $this->assertDatabaseHas('crm_tasks', ['title' => 'Non-WhatsApp follow-up']);
    }

    public function test_inactive_rules_and_other_triggers_are_ignored(): void
    {
        $lead = ServiceRequest::factory()->create();
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::LeadCreated, [], [['type' => 'create_task', 'title' => 'Inactive rule task']], isActive: false);
        $this->makeRule(WorkflowTrigger::LeadAssigned, [], [['type' => 'create_task', 'title' => 'Other trigger task']]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $this->assertDatabaseMissing('crm_tasks', ['title' => 'Inactive rule task']);
        $this->assertDatabaseMissing('crm_tasks', ['title' => 'Other trigger task']);
    }

    public function test_update_status_action_only_applies_active_status_slugs(): void
    {
        ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'مؤهل'],
            'slug' => 'qualified_by_rule',
            'outcome' => 'open',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'موقوف'],
            'slug' => 'disabled_status',
            'outcome' => 'open',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $lead = ServiceRequest::factory()->create();
        $originalStatus = $lead->refresh()->status;

        $this->makeRule(WorkflowTrigger::LeadCreated, [], [['type' => 'update_status', 'value' => 'disabled_status']]);
        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);
        $this->assertSame($originalStatus, $lead->refresh()->status);

        WorkflowRule::query()->delete();
        $this->makeRule(WorkflowTrigger::LeadCreated, [], [['type' => 'update_status', 'value' => 'qualified_by_rule']]);
        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);
        $this->assertSame('qualified_by_rule', $lead->refresh()->status);
    }

    public function test_assign_employee_action_assigns_the_employees_user(): void
    {
        $agent = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $agent->id]);
        $lead = ServiceRequest::factory()->create(['assigned_to' => null]);

        $this->makeRule(WorkflowTrigger::LeadCreated, [], [['type' => 'assign_employee', 'employee_id' => $employee->id]]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $this->assertSame($agent->id, $lead->refresh()->assigned_to);
    }

    public function test_assign_and_status_actions_do_nothing_for_deals(): void
    {
        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        $deal = Deal::factory()->create(['employee_id' => null]);

        $this->makeRule(WorkflowTrigger::DealCreated, [], [
            ['type' => 'assign_employee', 'employee_id' => $employee->id],
            ['type' => 'update_status', 'value' => 'anything'],
        ]);

        $this->service()->run(WorkflowTrigger::DealCreated, $deal);

        $this->assertNull($deal->refresh()->employee_id);
    }

    public function test_deal_stage_change_rule_uses_the_new_stage_from_context(): void
    {
        $deal = Deal::factory()->stage(DealStage::Proposal)->create();
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::DealStageChanged, [
            ['field' => 'new_stage', 'operator' => 'equals', 'value' => DealStage::Negotiation->value],
        ], [
            ['type' => 'create_task', 'title' => 'Prepare negotiation pack'],
        ]);

        $this->service()->run(WorkflowTrigger::DealStageChanged, $deal, ['new_stage' => DealStage::ClosedLost->value]);
        $this->assertDatabaseMissing('crm_tasks', ['title' => 'Prepare negotiation pack']);

        $this->service()->run(WorkflowTrigger::DealStageChanged, $deal, ['new_stage' => DealStage::Negotiation->value]);
        $this->assertDatabaseHas('crm_tasks', [
            'title' => 'Prepare negotiation pack',
            'entity_type' => Deal::class,
            'entity_id' => $deal->id,
        ]);
    }

    public function test_unknown_action_types_are_ignored(): void
    {
        $lead = ServiceRequest::factory()->create();
        CrmTask::query()->delete();

        $this->makeRule(WorkflowTrigger::LeadCreated, [], [['type' => 'send_fax']]);

        $this->service()->run(WorkflowTrigger::LeadCreated, $lead);

        $this->assertSame(0, CrmTask::query()->count());
    }
}
