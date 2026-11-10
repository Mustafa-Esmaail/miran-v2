<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ServiceRequestCategoryPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $modules = [
        'complaint_service_requests',
        'b2b_service_requests',
        'b2c_service_requests',
    ];

    /**
     * @var array<int, string>
     */
    private array $actions = ['viewAny', 'view', 'create', 'update', 'delete'];

    /**
     * Creates the permissions for the split ServiceRequest category resources
     * (Complaints / B2B / B2C) without syncing them to any role — grant
     * access per role manually via the Role admin UI.
     */
    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $count = 0;
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$module}.{$action}", 'guard_name' => self::GUARD],
                    []
                );
                $count++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info("Service request category permissions seeded ({$count} permissions, no role sync).");
    }
}
