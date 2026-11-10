<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignmentPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * assignments.* is also auto-discovered by PermissionSeeder (AssignmentResource
     * exists), but assignment_submissions.* has no Filament resource of its own
     * (it's only a nested RelationManager) so it is never auto-discovered, and the
     * "grade" ability is a custom, non-CRUD permission. Both are seeded here.
     *
     * @var array<int, string>
     */
    private array $permissions = [
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
    ];

    /**
     * @var array<int, string>
     */
    private array $roles = ['admin', 'super-admin', 'management', 'training'];

    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($this->permissions)->map(
            fn (string $name): Permission => Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                []
            )
        );

        foreach ($this->roles as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->first();

            if (! $role) {
                $this->command->warn("Role [{$roleName}] not found — skipped.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("  {$roleName}: assignment permissions granted (additive)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Assignment permissions seeded (additive — no other role permissions were touched).');
    }
}
