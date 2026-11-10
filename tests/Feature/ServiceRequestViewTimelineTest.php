<?php

namespace Tests\Feature;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Enums\ServiceRequestActivityType;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewB2bServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Schemas\ServiceRequestInfolist;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Employee;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use App\Support\Crm\LeadTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceRequestViewTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsLeadViewer(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $permissions = ['service_requests.viewAny', 'service_requests.view', 'b2b_service_requests.viewAny', 'b2b_service_requests.view'];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function makeStatus(string $slug, string $name, ?string $outcome = 'open'): ServiceRequestStatus
    {
        return ServiceRequestStatus::query()->create([
            'name' => ['en' => $name, 'ar' => $name],
            'slug' => $slug,
            'outcome' => $outcome,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Creates a lead with the three kinds of history, each at a distinct time.
     *
     * @return array{lead: ServiceRequest, employee: Employee}
     */
    private function leadWithHistory(): array
    {
        $lead = ServiceRequest::factory()->create(['source' => 'form']);
        ServiceRequestActivity::query()->where('service_request_id', $lead->id)->update(['created_at' => now()->subDays(10)]);

        $employee = Employee::factory()->create(['name' => 'Timeline Agent']);

        ServiceRequestActivity::query()->create([
            'service_request_id' => $lead->id,
            'type' => ServiceRequestActivityType::NoteAdded->value,
            'note' => 'Oldest note on the lead',
            'created_at' => now()->subDays(3),
        ]);

        CrmActivity::factory()->create([
            'entity_type' => ServiceRequest::class,
            'entity_id' => $lead->id,
            'type' => CrmActivityType::Call->value,
            'outcome' => CrmActivityOutcome::Interested->value,
            'subject' => 'Middle call with the lead',
            'body' => null,
            'employee_id' => $employee->id,
            'created_at' => now()->subDays(2),
        ]);

        $task = CrmTask::factory()->create([
            'entity_type' => ServiceRequest::class,
            'entity_id' => $lead->id,
            'title' => 'Newest task send brochure',
            'employee_id' => $employee->id,
        ]);
        CrmTask::query()->whereKey($task->id)->update(['created_at' => now()->subDay()]);

        return ['lead' => $lead, 'employee' => $employee];
    }

    public function test_view_page_renders_the_lead_summary(): void
    {
        $this->actingAsLeadViewer();
        $this->makeStatus('callback', 'Call back later');
        $assignee = User::factory()->create(['name' => 'Summary Assignee']);

        $lead = ServiceRequest::factory()->create([
            'source' => 'form',
            'name' => 'Summary Lead Name',
            'phone' => '0551234567',
            'status' => 'callback',
            'assigned_to' => $assignee->id,
        ]);

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSee('Summary Lead Name')
            ->assertSee('0551234567')
            ->assertSee('tel:', false)
            ->assertSee('Call back later')
            ->assertSee('Summary Assignee');
    }

    public function test_timeline_merges_all_three_sources_newest_first(): void
    {
        $this->actingAsLeadViewer();
        ['lead' => $lead] = $this->leadWithHistory();

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSeeInOrder([
                'Newest task send brochure',
                'Middle call with the lead',
                'Oldest note on the lead',
            ])
            ->assertSee('Timeline Agent')
            ->assertDontSee(__('admin.lead_view.timeline_empty_heading'));
    }

    public function test_timeline_builder_returns_entries_from_every_source_in_date_order(): void
    {
        ['lead' => $lead] = $this->leadWithHistory();

        $entries = LeadTimeline::for($lead);

        $this->assertSame(
            [LeadTimeline::SOURCE_CRM_TASK, LeadTimeline::SOURCE_CRM_ACTIVITY, LeadTimeline::SOURCE_LOG, LeadTimeline::SOURCE_LOG],
            $entries->pluck('source')->all(),
        );
        $this->assertSame('Timeline Agent', $entries->first()['actor']);
        $this->assertSame('Oldest note on the lead', $entries->get(2)['body']);
    }

    public function test_timeline_resolves_status_names_and_assignee_names(): void
    {
        $this->makeStatus('fresh', 'Fresh lead');
        $this->makeStatus('callback', 'Call back later');
        $agent = User::factory()->create(['name' => 'Resolved Agent']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'status' => 'fresh']);

        $lead->update(['status' => 'callback', 'assigned_to' => $agent->id]);

        $entries = LeadTimeline::for($lead);
        $status = $entries->firstWhere('title', __('admin.options.status_changed'));
        $assigned = $entries->firstWhere('title', __('admin.options.assigned_changed'));

        $this->assertSame('Fresh lead', $status['from']);
        $this->assertSame('Call back later', $status['to']);
        $this->assertSame('Resolved Agent', $assigned['to']);
    }

    public function test_timeline_shows_empty_state_when_the_lead_has_no_history(): void
    {
        $this->actingAsLeadViewer();
        $lead = ServiceRequest::factory()->create(['source' => 'form']);
        ServiceRequestActivity::query()->where('service_request_id', $lead->id)->delete();

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSee(__('admin.lead_view.timeline_empty_heading'));
    }

    public function test_converted_order_link_is_shown_only_for_converted_leads(): void
    {
        $this->actingAsLeadViewer();
        $order = Order::factory()->create(['order_no' => 'ORDER-LINKED-1']);
        $converted = ServiceRequest::factory()->create(['source' => 'form', 'converted_order_id' => $order->id, 'converted_at' => now()]);
        $open = ServiceRequest::factory()->create(['source' => 'form']);
        $orderUrl = OrderResource::getUrl('view', ['record' => $order->id]);

        Livewire::test(ViewServiceRequest::class, ['record' => $converted->getKey()])
            ->assertOk()
            ->assertSee('ORDER-LINKED-1')
            ->assertSee($orderUrl, false);

        Livewire::test(ViewServiceRequest::class, ['record' => $open->getKey()])
            ->assertOk()
            ->assertDontSee('ORDER-LINKED-1')
            ->assertDontSee($orderUrl, false);
    }

    public function test_overdue_follow_up_is_flagged_only_for_open_statuses(): void
    {
        $this->actingAsLeadViewer();
        $this->makeStatus('callback', 'Call back later', 'open');
        $this->makeStatus('won_deal', 'Won deal', 'won');

        $overdue = ServiceRequest::factory()->create(['source' => 'form', 'status' => 'callback', 'status_date' => now()->subDays(2)]);
        $upcoming = ServiceRequest::factory()->create(['source' => 'form', 'status' => 'callback', 'status_date' => now()->addDays(2)]);
        $closed = ServiceRequest::factory()->create(['source' => 'form', 'status' => 'won_deal', 'status_date' => now()->subDays(2)]);

        $this->assertTrue(ServiceRequestInfolist::isFollowUpOverdue($overdue->load('statusModel')));
        $this->assertFalse(ServiceRequestInfolist::isFollowUpOverdue($upcoming->load('statusModel')));
        $this->assertFalse(ServiceRequestInfolist::isFollowUpOverdue($closed->load('statusModel')));
        $this->assertSame([$overdue->id], ServiceRequest::query()->followUpOverdue()->pluck('id')->all());

        Livewire::test(ViewServiceRequest::class, ['record' => $overdue->getKey()])
            ->assertOk()
            ->assertSee(__('admin.lead_view.overdue'));

        Livewire::test(ViewServiceRequest::class, ['record' => $upcoming->getKey()])
            ->assertOk()
            ->assertDontSee(__('admin.lead_view.overdue'));
    }

    public function test_b2b_view_page_uses_the_shared_summary_and_timeline(): void
    {
        $this->actingAsLeadViewer();
        $lead = ServiceRequest::factory()->create([
            'source' => 'corporate_training',
            'name' => 'B2B Summary Lead',
            'company_name' => 'Acme Training Co',
        ]);

        Livewire::test(ViewB2bServiceRequest::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSee('B2B Summary Lead')
            ->assertSee('Acme Training Co')
            ->assertSee(__('admin.lead_view.timeline'));
    }
}
