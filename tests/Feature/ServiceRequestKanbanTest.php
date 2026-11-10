<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanB2bServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanB2cServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanComplaintServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanServiceRequests;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestKanbanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithB2cAccess(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'b2c_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_kanban_board_renders_and_groups_requests_by_stage(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stagedStatus = ServiceRequestStatus::query()->where('slug', 'no_answer_2')->first();
        $this->assertNotNull($stagedStatus);
        $this->assertNotNull($stagedStatus->stage);

        $staged = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0511111111',
            'status' => 'no_answer_2',
        ]);

        $unassigned = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0522222222',
            'status' => 'new',
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->assertOk()
            ->assertSee($staged->phone)
            ->assertSee($unassigned->phone)
            ->assertSee($stagedStatus->stage->getTranslatedName());
    }

    public function test_dropping_into_a_single_status_stage_applies_that_status_directly(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'pay')->first();
        $this->assertNotNull($stage, 'Expected the seeded Talabat pay stage to exist.');
        $this->assertSame(1, ServiceRequestStatus::query()->where('stage_id', $stage->id)->count());

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id]);

        $this->assertSame('payment_completed', $record->fresh()->status);
    }

    public function test_dropping_into_a_multi_status_stage_opens_the_status_chooser(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'follow_up')->first();
        $this->assertNotNull($stage);
        $this->assertGreaterThan(1, ServiceRequestStatus::query()->where('stage_id', $stage->id)->count());

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id])
            ->assertActionMounted('pickStatus');

        $this->assertSame('new', $record->fresh()->status);
    }

    public function test_complaint_kanban_board_only_shows_complaint_sourced_requests(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $complaint = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'phone' => '0533333333',
            'status' => 'new',
        ]);

        $lead = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0544444444',
            'status' => 'new',
        ]);

        Livewire::test(KanbanComplaintServiceRequests::class)
            ->assertOk()
            ->assertSee($complaint->phone)
            ->assertDontSee($lead->phone);
    }

    public function test_complaint_kanban_board_does_not_leak_talabat_stage_columns(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $talabatStage = ServiceRequestStage::query()->where('slug', 'contact')->first();
        $this->assertNotNull($talabatStage, 'Expected the seeded Talabat contact stage to exist.');

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'phone' => '0599999999',
            'status' => 'new',
        ]);

        Livewire::test(KanbanComplaintServiceRequests::class)
            ->assertOk()
            ->assertDontSee($talabatStage->getTranslatedName())
            ->assertSee(__('admin.options.unassigned'));
    }

    public function test_complaint_kanban_board_shows_its_own_stages(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $generalStage = ServiceRequestStage::query()->where('slug', 'general')->first();
        $this->assertNotNull($generalStage, 'Expected the seeded Shakawi general stage to exist.');

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'phone' => '0511223344',
            'status' => 'complaint_inquiry',
        ]);

        Livewire::test(KanbanComplaintServiceRequests::class)
            ->assertOk()
            ->assertSee($record->phone)
            ->assertSee($generalStage->getTranslatedName());
    }

    public function test_complaint_kanban_board_shows_its_stages_even_with_no_records_yet(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $generalStage = ServiceRequestStage::query()->where('slug', 'general')->first();
        $this->assertNotNull($generalStage);

        Livewire::test(KanbanComplaintServiceRequests::class)
            ->assertOk()
            ->assertSee($generalStage->getTranslatedName());
    }

    public function test_b2b_kanban_board_only_shows_corporate_training_requests(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'b2b_service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $b2b = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::CorporateTraining,
            'phone' => '0555555555',
            'status' => 'new',
        ]);

        $lead = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0566666666',
            'status' => 'new',
        ]);

        Livewire::test(KanbanB2bServiceRequests::class)
            ->assertOk()
            ->assertSee($b2b->phone)
            ->assertDontSee($lead->phone);
    }

    public function test_base_service_request_kanban_board_shows_every_source(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->firstOrCreate(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $admin->assignRole($role);
        $admin->givePermissionTo(['service_requests.viewAny', 'service_requests.view']);

        $complaint = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Complaint,
            'phone' => '0577777777',
            'status' => 'new',
        ]);

        $lead = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0588888888',
            'status' => 'new',
        ]);

        Livewire::test(KanbanServiceRequests::class)
            ->assertOk()
            ->assertSee($complaint->phone)
            ->assertSee($lead->phone);
    }

    public function test_dropping_into_a_single_status_stage_that_requires_a_date_asks_for_it_first(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'pay')->first();
        $this->assertNotNull($stage);
        ServiceRequestStatus::query()->where('slug', 'payment_completed')->update(['requires_date' => true]);

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
            'status_date' => null,
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id])
            ->assertActionMounted('setStatusDate')
            ->setActionData(['status_date' => null])
            ->callMountedAction()
            ->assertHasActionErrors(['status_date' => 'required']);

        $record->refresh();
        $this->assertSame('new', $record->status);
        $this->assertNull($record->status_date);
    }

    public function test_dropping_into_a_single_status_stage_that_requires_a_date_saves_status_and_date(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'pay')->first();
        $this->assertNotNull($stage);
        ServiceRequestStatus::query()->where('slug', 'payment_completed')->update(['requires_date' => true]);

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
            'status_date' => null,
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id])
            ->assertActionMounted('setStatusDate')
            ->setActionData(['status_date' => '2026-12-01 10:00:00'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $record->refresh();
        $this->assertSame('payment_completed', $record->status);
        $this->assertSame('2026-12-01 10:00', $record->status_date?->format('Y-m-d H:i'));
    }

    public function test_choosing_a_status_that_requires_a_date_from_the_status_picker_requires_the_date(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'follow_up')->first();
        $this->assertNotNull($stage);
        $datedStatus = ServiceRequestStatus::query()->active()->where('stage_id', $stage->id)->orderBy('sort_order')->first();
        $this->assertNotNull($datedStatus);
        $datedStatus->update(['requires_date' => true]);

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
            'status_date' => null,
        ]);

        $component = Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id])
            ->assertActionMounted('pickStatus')
            ->setActionData(['status' => $datedStatus->slug, 'status_date' => null])
            ->callMountedAction()
            ->assertHasActionErrors(['status_date' => 'required']);

        $this->assertSame('new', $record->fresh()->status);

        $component
            ->setActionData(['status' => $datedStatus->slug, 'status_date' => '2026-12-02 11:30:00'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $record->refresh();
        $this->assertSame($datedStatus->slug, $record->status);
        $this->assertSame('2026-12-02 11:30', $record->status_date?->format('Y-m-d H:i'));
    }

    public function test_dropping_into_a_status_without_a_date_requirement_keeps_the_existing_status_date(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $stage = ServiceRequestStage::query()->where('slug', 'pay')->first();
        $this->assertNotNull($stage);
        ServiceRequestStatus::query()->where('slug', 'payment_completed')->update(['requires_date' => false]);

        $record = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'new',
            'status_date' => '2026-10-01 09:00:00',
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->call('onStatusChanged', $record->id, (string) $stage->id, [], [(string) $record->id])
            ->assertActionNotMounted('setStatusDate');

        $record->refresh();
        $this->assertSame('payment_completed', $record->status);
        $this->assertSame('2026-10-01 09:00', $record->status_date?->format('Y-m-d H:i'));
    }

    public function test_assignee_filter_narrows_the_board_to_that_employee(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();

        $firstRecord = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0512340001',
            'status' => 'new',
            'assigned_to' => $firstAssignee->id,
        ]);

        $secondRecord = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'phone' => '0512340002',
            'status' => 'new',
            'assigned_to' => $secondAssignee->id,
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->assertSee($firstRecord->phone)
            ->assertSee($secondRecord->phone)
            ->set('assignedTo', (string) $firstAssignee->id)
            ->assertSee($firstRecord->phone)
            ->assertDontSee($secondRecord->phone)
            ->set('assignedTo', '')
            ->assertSee($secondRecord->phone);
    }

    public function test_card_shows_the_lead_name_and_marks_an_overdue_follow_up(): void
    {
        $this->actingAsAdminWithB2cAccess();

        $overdue = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'name' => 'Overdue Kanban Lead',
            'phone' => '0512340003',
            'status' => 'new',
            'status_date' => now()->subDays(2),
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->assertSee($overdue->name)
            ->assertSee(__('admin.kanban.follow_up_overdue'))
            ->assertSeeHtml('data-follow-up-overdue');
    }

    public function test_card_does_not_mark_a_future_or_closed_follow_up_as_overdue(): void
    {
        $this->actingAsAdminWithB2cAccess();

        ServiceRequestStatus::query()->where('slug', 'payment_completed')->update(['outcome' => 'won']);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'name' => 'Upcoming Kanban Lead',
            'status' => 'new',
            'status_date' => now()->addDays(2),
        ]);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'name' => 'Won Kanban Lead',
            'status' => 'payment_completed',
            'status_date' => now()->subDays(2),
        ]);

        Livewire::test(KanbanB2cServiceRequests::class)
            ->assertSee('Upcoming Kanban Lead')
            ->assertSee('Won Kanban Lead')
            ->assertDontSeeHtml('data-follow-up-overdue');
    }
}
