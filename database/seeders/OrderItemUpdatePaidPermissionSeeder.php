<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OrderItemUpdatePaidPermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'order_items.update_paid',
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
                $this->command->warn("Role [{$roleName}] not found — skipped.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("  {$roleName}: order item paid-edit permission granted (additive)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Order item paid-edit permission seeded (additive — no other role permissions were touched).');
    }
}
