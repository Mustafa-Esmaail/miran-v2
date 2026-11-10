<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BroadcastMailRecipientPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'broadcast_mail_recipients.viewAny',
        'broadcast_mail_recipients.view',
        'broadcast_mail_recipients.create',
        'broadcast_mail_recipients.update',
        'broadcast_mail_recipients.delete',
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
            $this->command->info("  {$roleName}: broadcast mail recipient permissions granted (additive)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Broadcast mail recipient permissions seeded (additive — no other role permissions were touched).');
    }
}
