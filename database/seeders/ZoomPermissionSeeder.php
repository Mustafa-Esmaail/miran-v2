<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Standalone, production-safe seeder for Zoom permissions.
 *
 * Unlike PermissionSeeder, this only creates the zoom.* permission records
 * and grants them additively (givePermissionTo, never syncPermissions), so
 * it never resets or removes any other permission already assigned to a role.
 */
class ZoomPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permissionName) {
            Permission::query()->firstOrCreate(
                ['name' => $permissionName, 'guard_name' => self::GUARD],
                []
            );
        }

        foreach ($this->roles() as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->first();

            if (! $role) {
                $this->command->warn("Role [{$roleName}] not found — skipped.");

                continue;
            }

            $role->givePermissionTo($this->permissions());
            $this->command->info("  {$roleName}: zoom permissions granted (additive)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Zoom permissions seeded (additive — no other role permissions were touched).');
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        return [
            'zoom.create_meetings',
            'zoom.sync_attendance',
            'zoom.sync_recordings',
            'zoom.view_live_attendance',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function roles(): array
    {
        return [
            'course_manager',
            'training_coordinator',
        ];
    }
}
