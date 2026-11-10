<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MarketingSourceReportPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'marketing_source_report.viewAny',
    ];

    /**
     * Roles granted access to the new report page.
     * Excludes 'operation' and 'sales', matching how the other commerce
     * report pages are scoped in OrderReportPermissionsSeeder.
     *
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
                $this->command->warn("Role [{$roleName}] not found — run RolesSeeder first.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("  {$roleName}: marketing source report permission assigned");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Marketing source report permission seeded.');
    }
}
