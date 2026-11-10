<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequestStages\Pages\CreateServiceRequestStage;
use App\Filament\Admin\Resources\ServiceRequestStages\Pages\ListServiceRequestStages;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestStageResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithPermissions(array $actions): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);

        foreach ($actions as $action) {
            $permission = Permission::query()->firstOrCreate(['name' => "service_request_stages.{$action}", 'guard_name' => 'admin']);
            $role->givePermissionTo($permission);
        }

        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_list_page_shows_stages_for_their_type(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny']);

        $type = ServiceRequestType::query()->create([
            'name' => ['ar' => 'شكاوى'],
            'slug' => 'complaints_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ServiceRequestStage::query()->create([
            'type_id' => $type->id,
            'name' => ['ar' => 'مرحلة تجريبية'],
            'slug' => 'trial_stage',
            'color' => '#3b82f6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(ListServiceRequestStages::class)
            ->assertOk()
            ->assertSee('مرحلة تجريبية');
    }

    public function test_stage_can_be_created_under_a_type(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny', 'create']);

        $type = ServiceRequestType::query()->create([
            'name' => ['ar' => 'طلبات'],
            'slug' => 'leads_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(CreateServiceRequestStage::class)
            ->fillForm([
                'type_id' => $type->id,
                'name.ar' => 'مرحلة جديدة',
                'slug' => 'new_stage',
                'color' => '#22c55e',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('service_request_stages', [
            'type_id' => $type->id,
            'slug' => 'new_stage',
        ]);
    }

    public function test_slug_must_be_unique_within_the_same_type(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny', 'create']);

        $type = ServiceRequestType::query()->create([
            'name' => ['ar' => 'طلبات'],
            'slug' => 'leads_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ServiceRequestStage::query()->create([
            'type_id' => $type->id,
            'name' => ['ar' => 'مرحلة موجودة'],
            'slug' => 'duplicate_slug',
            'color' => '#3b82f6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(CreateServiceRequestStage::class)
            ->fillForm([
                'type_id' => $type->id,
                'name.ar' => 'مرحلة أخرى',
                'slug' => 'duplicate_slug',
                'color' => '#22c55e',
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }
}
