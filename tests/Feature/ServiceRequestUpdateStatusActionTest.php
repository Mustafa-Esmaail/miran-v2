<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\CrmActivity;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceRequestUpdateStatusActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithServiceRequestPermissions(User $user): void
    {
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view', 'update'] as $action) {
            Permission::query()->firstOrCreate(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['service_requests.viewAny', 'service_requests.view', 'service_requests.update']);
    }

    public function test_update_status_action_changes_status_and_logs_a_crm_activity(): void
    {
        ServiceRequestStatus::query()->updateOrCreate(['slug' => 'new'], [
            'name' => ['en' => 'New', 'ar' => 'جديد'],
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ServiceRequestStatus::query()->updateOrCreate(['slug' => 'in_progress'], [
            'name' => ['en' => 'In Progress', 'ar' => 'قيد التنفيذ'],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create();
        $this->actingAsWithServiceRequestPermissions($user);

        $serviceRequest = ServiceRequest::factory()->create(['status' => 'new']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->callTableAction('update_status', $serviceRequest, data: [
                'status' => 'in_progress',
                'type' => 'call',
                'outcome' => 'reached',
                'completed_at' => now(),
                'body' => 'Talked to the client about their request.',
            ]);

        $serviceRequest->refresh();
        $this->assertSame('in_progress', $serviceRequest->status);

        $activity = CrmActivity::query()
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $serviceRequest->id)
            ->firstOrFail();

        $this->assertSame('call', $activity->type->value);
        $this->assertSame('reached', $activity->outcome->value);
        $this->assertSame('Talked to the client about their request.', $activity->body);
    }

    public function test_update_status_action_is_hidden_without_update_permission(): void
    {
        ServiceRequestStatus::query()->updateOrCreate(['slug' => 'new'], [
            'name' => ['en' => 'New', 'ar' => 'جديد'],
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->firstOrCreate(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['service_requests.viewAny', 'service_requests.view']);

        $serviceRequest = ServiceRequest::factory()->create(['status' => 'new']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertTableActionHidden('update_status', $serviceRequest);
    }
}
