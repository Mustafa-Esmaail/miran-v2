<?php

namespace Tests\Feature;

use Database\Seeders\AssignmentPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignmentPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_all_assignment_permissions(): void
    {
        $this->seed(AssignmentPermissionSeeder::class);

        foreach ([
            'assignments.viewAny',
            'assignments.view',
            'assignments.create',
            'assignments.update',
            'assignments.delete',
            'assignment_submissions.viewAny',
            'assignment_submissions.view',
            'assignment_submissions.create',
            'assignment_submissions.update',
            'assignment_submissions.delete',
            'assignment_submissions.grade',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }
    }

    public function test_it_grants_permissions_additively_without_touching_existing_role_permissions(): void
    {
        $role = Role::query()->create(['name' => 'management', 'guard_name' => 'admin']);
        $unrelatedPermission = Permission::query()->create(['name' => 'courses.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($unrelatedPermission);

        $this->seed(AssignmentPermissionSeeder::class);

        $role->refresh();
        $permissionNames = $role->permissions->pluck('name');

        $this->assertTrue($permissionNames->contains('courses.viewAny'));
        $this->assertTrue($permissionNames->contains('assignments.create'));
        $this->assertTrue($permissionNames->contains('assignment_submissions.grade'));
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $this->seed(AssignmentPermissionSeeder::class);
        $this->seed(AssignmentPermissionSeeder::class);

        $this->assertSame(
            1,
            Permission::query()->where('name', 'assignment_submissions.grade')->where('guard_name', 'admin')->count()
        );
    }

    public function test_it_skips_missing_roles_without_error(): void
    {
        $this->seed(AssignmentPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'assignment_submissions.grade',
            'guard_name' => 'admin',
        ]);
    }
}
