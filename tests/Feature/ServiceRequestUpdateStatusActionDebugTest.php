<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceRequestUpdateStatusActionDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_surface_validation_errors(): void
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
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view', 'update'] as $action) {
            Permission::query()->firstOrCreate(['name' => "service_requests.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['service_requests.viewAny', 'service_requests.view', 'service_requests.update']);

        $serviceRequest = ServiceRequest::factory()->create(['status' => 'new']);

        $test = Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->mountTableAction('update_status', $serviceRequest);

        fwrite(STDERR, "\n=== HAS REDIRECT AFTER MOUNT ===\n".json_encode(\Livewire\store($test->instance())->has('redirect'))."\n");
        fwrite(STDERR, "\n=== MOUNTED ACTIONS AFTER MOUNT ===\n".json_encode($test->instance()->mountedActions, JSON_PRETTY_PRINT)."\n");

        $test->assertHasNoTableActionErrors();
    }
}
