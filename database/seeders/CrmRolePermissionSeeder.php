<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CrmRolePermissionSeeder extends Seeder
{
    private const GUARD = 'admin';

    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix() as $roleName => $permissionNames) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->first();

            if (! $role) {
                $this->command->warn("Role [{$roleName}] not found — run RolesSeeder first.");

                continue;
            }

            $permissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->where('guard_name', self::GUARD)
                ->get();

            $role->syncPermissions($permissions);

            $this->command->info("  {$roleName}: {$permissions->count()} permissions assigned");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('CRM role permissions seeded.');
    }

    /**
     * Permission matrix for each CRM role.
     * admin/super-admin keep their existing all-permissions grants from PermissionSeeder.
     *
     * @return array<string, list<string>>
     */
    private function matrix(): array
    {
        $all = ['viewAny', 'view', 'create', 'update', 'delete'];
        $read = ['viewAny', 'view'];
        $write = ['viewAny', 'view', 'create', 'update'];

        $perms = fn (string $resource, array $actions): array => array_map(
            fn (string $action): string => "{$resource}.{$action}",
            $actions
        );

        return [
            // ─── crm_manager ────────────────────────────────────────────────
            // Full CRM + employee management
            'crm_manager' => [
                ...$perms('employees', $all),
                ...$perms('departments', $all),
                ...$perms('teams', $all),
                ...$perms('service_requests', $all),
                ...$perms('service_request_activities', $all),
                ...$perms('crm_activities', $all),
                ...$perms('crm_tasks', $all),
                ...$perms('deals', $all),
                ...$perms('companies', $all),
                ...$perms('quotes', $all),
                ...$perms('commission_ledgers', $all),
                'commission_ledgers.approve',
                'commission_ledgers.mark_paid',
                ...$perms('sales_targets', $all),
                ...$perms('share_links', $all),
            ],

            // ─── department_manager ─────────────────────────────────────────
            // Department-scoped CRM; read-only on employees/departments/teams
            'department_manager' => [
                ...$perms('employees', $read),
                ...$perms('departments', $read),
                ...$perms('teams', $read),
                ...$perms('service_requests', $all),
                ...$perms('service_request_activities', $all),
                ...$perms('crm_activities', $all),
                ...$perms('crm_tasks', $all),
                ...$perms('deals', $all),
                ...$perms('companies', $all),
                ...$perms('quotes', $all),
                ...$perms('commission_ledgers', $read),
                ...$perms('sales_targets', $all),
                ...$perms('share_links', $all),
            ],

            // ─── team_lead ───────────────────────────────────────────────────
            // Team-scoped CRM; read-only on employees/commissions; full targets
            'team_lead' => [
                ...$perms('employees', $read),
                ...$perms('departments', $read),
                ...$perms('teams', $read),
                ...$perms('service_requests', $all),
                ...$perms('service_request_activities', $all),
                ...$perms('crm_activities', $all),
                ...$perms('crm_tasks', $all),
                ...$perms('deals', $all),
                ...$perms('companies', $all),
                ...$perms('quotes', $all),
                ...$perms('commission_ledgers', $read),
                ...$perms('sales_targets', $all),
                ...$perms('share_links', $all),
            ],

            // ─── sales_agent ─────────────────────────────────────────────────
            // Own-data-only; no delete anywhere; no company management
            'sales_agent' => [
                ...$perms('service_requests', $write),
                ...$perms('service_request_activities', $all),
                ...$perms('crm_activities', $write),
                ...$perms('crm_tasks', $write),
                ...$perms('deals', $write),
                ...$perms('companies', $read),
                ...$perms('quotes', $write),
                ...$perms('commission_ledgers', $read),
                ...$perms('sales_targets', $read),
                ...$perms('share_links', $all),
            ],

            // ─── support_agent ───────────────────────────────────────────────
            // Service requests + activities/tasks only; no deals or commissions
            'support_agent' => [
                ...$perms('service_requests', $write),
                ...$perms('service_request_activities', $all),
                ...$perms('crm_activities', $write),
                ...$perms('crm_tasks', $write),
            ],
        ];
    }
}
