<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequestStatuses\Pages\CreateServiceRequestStatus;
use App\Filament\Admin\Resources\ServiceRequestStatuses\Pages\EditServiceRequestStatus;
use App\Filament\Admin\Resources\ServiceRequestStatuses\Pages\ListServiceRequestStatuses;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestStatusResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithPermissions(array $actions): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);

        foreach ($actions as $action) {
            $permission = Permission::query()->firstOrCreate(['name' => "service_request_statuses.{$action}", 'guard_name' => 'admin']);
            $role->givePermissionTo($permission);
        }

        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_list_page_shows_seeded_statuses(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny']);

        ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'حالة تجريبية'],
            'slug' => 'test_status_a',
            'color' => '#3b82f6',
            'outcome' => 'open',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(ListServiceRequestStatuses::class)
            ->assertOk()
            ->assertSee('حالة تجريبية');
    }

    public function test_status_can_be_created_with_color_and_outcome(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny', 'create']);

        Livewire::test(CreateServiceRequestStatus::class)
            ->fillForm([
                'name.ar' => 'حالة جديدة',
                'slug' => 'new_custom_status',
                'color' => '#22c55e',
                'outcome' => 'won',
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('service_request_statuses', [
            'slug' => 'new_custom_status',
            'color' => '#22c55e',
            'outcome' => 'won',
        ]);
    }

    public function test_marking_a_status_as_default_unsets_the_previous_default(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny', 'update']);

        $original = ServiceRequestStatus::query()->where('slug', 'new')->first();
        $this->assertNotNull($original);
        $this->assertTrue((bool) $original->is_default);

        $other = ServiceRequestStatus::query()->create([
            'name' => ['ar' => 'حالة أخرى'],
            'slug' => 'another_status',
            'color' => '#6b7280',
            'outcome' => 'open',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        Livewire::test(EditServiceRequestStatus::class, ['record' => $other->getRouteKey()])
            ->fillForm(['is_default' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue((bool) $other->fresh()->is_default);
        $this->assertFalse((bool) $original->fresh()->is_default);
    }

    public function test_status_can_be_assigned_to_a_stage_under_a_type(): void
    {
        $this->actingAsAdminWithPermissions(['viewAny', 'create']);

        $type = ServiceRequestType::query()->create([
            'name' => ['ar' => 'شكاوى'],
            'slug' => 'complaints_type',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $stage = ServiceRequestStage::query()->create([
            'type_id' => $type->id,
            'name' => ['ar' => 'مرحلة جديدة'],
            'slug' => 'new_stage',
            'color' => '#3b82f6',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(CreateServiceRequestStatus::class)
            ->fillForm([
                'name.ar' => 'حالة مرتبطة بمرحلة',
                'slug' => 'stage_linked_status',
                'color' => '#22c55e',
                'outcome' => 'won',
                'type_id' => $type->id,
                'stage_id' => $stage->id,
                'is_active' => true,
                'sort_order' => 6,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('service_request_statuses', [
            'slug' => 'stage_linked_status',
            'stage_id' => $stage->id,
        ]);
    }
}
