<?php

namespace Tests\Feature;

use Database\Seeders\ZoomPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ZoomPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_all_zoom_permissions(): void
    {
        $this->seed(ZoomPermissionSeeder::class);

        foreach ([
            'zoom.create_meetings',
            'zoom.sync_attendance',
            'zoom.sync_recordings',
            'zoom.view_live_attendance',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }
    }

    public function test_it_grants_permissions_additively_without_touching_existing_role_permissions(): void
    {
        $role = Role::query()->create(['name' => 'course_manager', 'guard_name' => 'admin']);
        $unrelatedPermission = Permission::query()->create(['name' => 'courses.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($unrelatedPermission);

        $this->seed(ZoomPermissionSeeder::class);

        $role->refresh();
        $permissionNames = $role->permissions->pluck('name');

        $this->assertTrue($permissionNames->contains('courses.viewAny'));
        $this->assertTrue($permissionNames->contains('zoom.view_live_attendance'));
        $this->assertTrue($permissionNames->contains('zoom.create_meetings'));
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(ZoomPermissionSeeder::class);
        $this->seed(ZoomPermissionSeeder::class);

        $this->assertSame(
            1,
            Permission::query()->where('name', 'zoom.view_live_attendance')->where('guard_name', 'admin')->count()
        );
    }

    public function test_it_skips_missing_roles_without_error(): void
    {
        $this->seed(ZoomPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'zoom.view_live_attendance',
            'guard_name' => 'admin',
        ]);
    }
}
