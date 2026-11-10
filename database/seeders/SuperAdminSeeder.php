<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    private const GUARD = 'admin';

    private const ROLE = 'super-admin';

    /**
     * Fetches every currently-existing "admin"-guard permission and syncs
     * the super-admin role to it. Run this last in DatabaseSeeder's call
     * list so it always picks up permissions created by any seeder that ran
     * before it (including ones, like ServiceRequestCategoryPermissionSeeder,
     * that deliberately don't sync their permissions to any role).
     */
    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->firstOrCreate(
            ['name' => self::ROLE, 'guard_name' => self::GUARD],
            []
        );

        $permissions = Permission::query()->where('guard_name', self::GUARD)->get();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info("Super-admin role synced with {$permissions->count()} permissions.");
    }
}
