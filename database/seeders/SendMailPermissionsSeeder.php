<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SendMailPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'send_mail.viewAny',
        'send_mail.view',
        'send_mail.create',
        'send_mail.update',
        'send_mail.delete',
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
            $this->command->info("  {$roleName}: send mail permissions granted (additive)");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Send mail permissions seeded (additive — no other role permissions were touched).');
    }
}
