<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Pages\CallAuditQueue;
use App\Models\CallEvaluation;
use App\Models\CallExclusion;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\CallQualitySeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CallAuditQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(CallQualitySeeder::class);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->actingAs($user, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function lead(string $phone, string $name, array $attributes = []): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'phone' => $phone,
            'name' => $name,
            'created_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_page_is_denied_without_the_evaluate_permission(): void
    {
        $this->userWith(['call_evaluations.viewAny']);

        $this->assertFalse(CallAuditQueue::canAccess());
        $this->get(CallAuditQueue::getUrl())->assertForbidden();
    }

    public function test_page_is_available_with_the_evaluate_permission(): void
    {
        $this->userWith(['call_evaluations.create', 'call_evaluations.view_all']);

        $this->assertTrue(CallAuditQueue::canAccess());
        $this->get(CallAuditQueue::getUrl())->assertOk();
    }

    public function test_it_lists_first_contact_leads_and_hides_evaluated_and_excluded_phones(): void
    {
        $user = $this->userWith(['call_evaluations.create', 'call_evaluations.view_all']);

        $this->lead('0551111111', 'First Contact Lead');
        $evaluated = $this->lead('0552222222', 'Evaluated Lead');
        $this->lead('0553333333', 'Excluded Lead');
        $this->lead('0554444444', 'Returning Lead');
        $this->lead('0554444444', 'Old Returning Lead', ['created_at' => now()->subDays(40)]);

        CallEvaluation::factory()->create(['call_phone' => $evaluated->phone]);
        CallExclusion::factory()->create(['phone' => '0553333333']);
        CallEvaluation::factory()->create(['evaluator_id' => $user->id]);

        $component = Livewire::test(CallAuditQueue::class)
            ->assertOk()
            ->assertSee('First Contact Lead')
            ->assertDontSee('Evaluated Lead')
            ->assertDontSee('Excluded Lead')
            ->assertDontSee('Returning Lead');

        $totals = $component->instance()->getQueueTotals();
        $this->assertSame(1, $totals['awaiting']);
        $this->assertSame(1, $totals['my_evaluations_today']);

        $component->set('filters.new_only', false)
            ->assertSee('First Contact Lead')
            ->assertSee('Returning Lead')
            ->assertDontSee('Old Returning Lead');
    }

    public function test_follow_up_action_excludes_the_phone_and_removes_the_row(): void
    {
        $this->userWith(['call_evaluations.create', 'call_evaluations.view_all', 'call_exclusions.create']);
        $lead = $this->lead('0555555555', 'Follow Up Lead');

        Livewire::test(CallAuditQueue::class)
            ->assertSee('Follow Up Lead')
            ->callAction(TestAction::make('followUp')->table($lead))
            ->assertNotified()
            ->assertDontSee('Follow Up Lead');

        $exclusion = CallExclusion::query()->sole();
        $this->assertSame('966555555555', $exclusion->phone);
        $this->assertSame($lead->id, $exclusion->service_request_id);
    }

    public function test_follow_up_action_is_hidden_without_the_exclusion_permission(): void
    {
        $this->userWith(['call_evaluations.create', 'call_evaluations.view_all']);
        $lead = $this->lead('0556666666', 'Hidden Follow Up Lead');

        Livewire::test(CallAuditQueue::class)
            ->assertActionHidden(TestAction::make('followUp')->table($lead))
            ->assertActionVisible(TestAction::make('evaluate')->table($lead));

        $this->assertSame(0, CallExclusion::query()->count());
    }

    public function test_evaluate_action_links_to_the_evaluation_form_for_the_lead(): void
    {
        $this->userWith(['call_evaluations.create', 'call_evaluations.view_all']);
        $lead = $this->lead('0557777777', 'Evaluate Me');

        $url = CallEvaluationResource::getUrl('create', ['service_request' => $lead->id]);

        $this->assertStringContainsString('service_request='.$lead->id, $url);

        Livewire::test(CallAuditQueue::class)
            ->assertActionHasUrl(TestAction::make('evaluate')->table($lead), $url);
    }

    public function test_agent_sees_only_their_own_leads_and_no_employee_filter(): void
    {
        $agent = $this->userWith(['call_evaluations.create']);
        Employee::factory()->create(['user_id' => $agent->id, 'role' => EmployeeRole::SalesPerson->value]);

        $colleague = User::factory()->create();
        Employee::factory()->create(['user_id' => $colleague->id, 'role' => EmployeeRole::SalesPerson->value]);

        $this->lead('0558888888', 'Agent Own Lead', ['assigned_to' => $agent->id]);
        $this->lead('0559999999', 'Colleague Lead', ['assigned_to' => $colleague->id]);

        $component = Livewire::test(CallAuditQueue::class)
            ->assertSee('Agent Own Lead')
            ->assertDontSee('Colleague Lead')
            ->assertFormFieldHidden('employee_id', 'filtersForm');

        $this->assertSame(1, $component->instance()->getQueueTotals()['awaiting']);
        $this->assertSame(1, $component->instance()->getQueueTotals()['employees']);
    }

    public function test_employee_filter_narrows_the_queue_for_a_viewer_of_everyone(): void
    {
        $this->userWith(['call_evaluations.create', 'call_evaluations.view_all']);

        $firstUser = User::factory()->create();
        $firstEmployee = Employee::factory()->create(['user_id' => $firstUser->id]);
        $secondUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $secondUser->id]);

        $this->lead('0550000001', 'First Employee Lead', ['assigned_to' => $firstUser->id]);
        $this->lead('0550000002', 'Second Employee Lead', ['assigned_to' => $secondUser->id]);

        Livewire::test(CallAuditQueue::class)
            ->assertSee('Second Employee Lead')
            ->set('filters.employee_id', (string) $firstEmployee->id)
            ->assertSee('First Employee Lead')
            ->assertDontSee('Second Employee Lead');
    }
}
