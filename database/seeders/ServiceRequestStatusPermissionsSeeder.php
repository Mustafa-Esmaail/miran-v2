<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ServiceRequestStatusPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'service_request_statuses.viewAny',
        'service_request_statuses.view',
        'service_request_statuses.create',
        'service_request_statuses.update',
        'service_request_statuses.delete',
    ];

    /**
     * Roles granted access to the new ServiceRequestStatus CRUD resource.
     * 'training' mirrors the access 'service_request_types' (its sibling
     * settings resource) gets via PermissionSeeder::crmModules().
     *
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
                $this->command->warn("Role [{$roleName}] not found — run RolesSeeder first.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("  {$roleName}: service request status permissions assigned");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Service request status permissions seeded.');
    }
}
