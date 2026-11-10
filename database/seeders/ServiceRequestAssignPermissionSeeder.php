<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ServiceRequestAssignPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'service_requests.assign',
    ];

    /**
     * Roles allowed to bulk-reassign service requests to a team/employee.
     * Mirrors the roles already granted full ('all') service_requests access
     * in CrmRolePermissionSeeder (crm_manager, team_lead), plus admin/super-admin.
     * sales_agent/support_agent keep only their own-data create/update access.
     *
     * @var array<int, string>
     */
    private array $roles = ['admin', 'super-admin', 'crm_manager', 'team_lead'];

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
            $this->command->info("  {$roleName}: service request assign permission assigned");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Service request assign permission seeded.');
    }
}
