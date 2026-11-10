<?php

namespace Tests\Unit;

use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_super_admin_with_every_existing_admin_guard_permission(): void
    {
        Permission::query()->create(['name' => 'courses.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'complaint_service_requests.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'irrelevant.viewAny', 'guard_name' => 'web']);

        $this->seed(SuperAdminSeeder::class);

        $role = Role::query()->where('name', 'super-admin')->where('guard_name', 'admin')->firstOrFail();
        $permissionNames = $role->permissions()->pluck('name')->sort()->values()->all();

        $this->assertSame(['complaint_service_requests.viewAny', 'courses.viewAny'], $permissionNames);
    }

    public function test_it_creates_the_super_admin_role_if_missing(): void
    {
        $this->assertDatabaseMissing('roles', ['name' => 'super-admin', 'guard_name' => 'admin']);

        $this->seed(SuperAdminSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'super-admin', 'guard_name' => 'admin']);
    }

    public function test_it_removes_permissions_the_role_had_but_no_longer_exist(): void
    {
        $role = Role::query()->create(['name' => 'super-admin', 'guard_name' => 'admin']);
        $stale = Permission::query()->create(['name' => 'removed_module.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($stale);

        $stale->delete();

        Permission::query()->create(['name' => 'courses.viewAny', 'guard_name' => 'admin']);

        $this->seed(SuperAdminSeeder::class);

        $permissionNames = $role->fresh()->permissions()->pluck('name')->all();
        $this->assertSame(['courses.viewAny'], $permissionNames);
    }
}
