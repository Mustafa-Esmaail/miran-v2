<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InvoicePermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'invoices.viewAny',
        'invoices.view',
        'invoices.create',
        'invoices.update',
        'invoices.delete',
    ];

    /**
     * Full invoice access: review, edit, attach files and change status.
     *
     * @var array<int, string>
     */
    private array $fullAccessRoles = ['admin', 'super-admin', 'accountant', 'management'];

    /**
     * Sales agents only create invoices and view their own — ownership is
     * scoped in ScopesInvoicesToSalesAgent, not by permission alone.
     *
     * @var array<int, string>
     */
    private array $createOnlyRoles = ['sales_agent'];

    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($this->permissions)->mapWithKeys(
            fn (string $name): array => [$name => Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                []
            )]
        );

        $this->grantPermissions($this->fullAccessRoles, $permissions->values());
        $this->grantPermissions(
            $this->createOnlyRoles,
            $permissions->only(['invoices.viewAny', 'invoices.view', 'invoices.create'])->values()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Invoice permissions seeded (additive — no other role permissions were touched).');
    }

    /**
     * @param  array<int, string>  $roles
     * @param  Collection<int, Permission>  $permissions
     */
    private function grantPermissions(array $roles, Collection $permissions): void
    {
        foreach ($roles as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->first();

            if (! $role) {
                $this->command->warn("Role [{$roleName}] not found — skipped.");

                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("  {$roleName}: invoice permissions granted (additive)");
        }
    }
}
