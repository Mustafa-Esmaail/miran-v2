<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Landing versions: the "Publish as new version" page action and the
 * landing versions report.
 *
 * php artisan db:seed --class=LandingVersionPermissionSeeder
 */
class LandingVersionPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'landing_versions.publish',
        'landing_version_report.viewAny',
    ];

    /**
     * @var array<int, string>
     */
    private array $roles = ['admin', 'super-admin', 'management'];

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
                $this->command?->warn("Role [{$roleName}] not found — run RolesSeeder first.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command?->info("  {$roleName}: landing version permissions assigned");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command?->info('Landing version permissions seeded.');
    }
}
