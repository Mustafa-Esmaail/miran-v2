<?php

namespace Database\Seeders;

use App\Models\CallQualityCriterion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Call-quality module: the reference system's six criteria and weights, the
 * module permissions, the quality_auditor role and per-role grants.
 * Runs after CrmRolePermissionSeeder (which syncs CRM role permissions) and only
 * adds permissions, so re-running either seeder in that order is safe.
 */
class CallQualitySeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * @var array<int, array{key: string, ar: string, en: string, weight: float}>
     */
    private const CRITERIA = [
        ['key' => 'c1', 'ar' => 'افتتاحية المكالمة', 'en' => 'Call opening', 'weight' => 12.5],
        ['key' => 'c2', 'ar' => 'الأسئلة الاستشارية', 'en' => 'Consultative questions', 'weight' => 20],
        ['key' => 'c3', 'ar' => 'حل الاعتراضات وإيجاد الحلول', 'en' => 'Handling objections', 'weight' => 20],
        ['key' => 'c4', 'ar' => 'أخذ موعد', 'en' => 'Booking an appointment', 'weight' => 12.5],
        ['key' => 'c6', 'ar' => 'إغلاق البيعة', 'en' => 'Closing the sale', 'weight' => 20],
        ['key' => 'c5', 'ar' => 'إنهاء المكالمة باحترافية', 'en' => 'Ending the call professionally', 'weight' => 12.5],
    ];

    public const PERMISSIONS = [
        'call_evaluations.viewAny',
        'call_evaluations.view',
        'call_evaluations.create',
        'call_evaluations.update',
        'call_evaluations.delete',
        'call_evaluations.discuss',
        'call_evaluations.approve',
        'call_evaluations.view_all',
        'call_quality_criteria.manage',
        'call_exclusions.create',
        'call_exclusions.delete',
    ];

    public function run(): void
    {
        Config::set('auth.defaults.guard', self::GUARD);
        Config::set('permission.defaults.guard', self::GUARD);

        $this->seedCriteria();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        Role::query()->firstOrCreate(['name' => 'quality_auditor', 'guard_name' => self::GUARD]);

        foreach ($this->matrix() as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', self::GUARD)->first();

            if (! $role) {
                $this->command?->warn("Role [{$roleName}] not found — run RolesSeeder first.");

                continue;
            }

            $role->givePermissionTo($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command?->info('Call quality criteria and permissions seeded.');
    }

    private function seedCriteria(): void
    {
        foreach (self::CRITERIA as $order => $criterion) {
            CallQualityCriterion::query()->firstOrCreate(
                ['key' => $criterion['key']],
                [
                    'name' => ['ar' => $criterion['ar'], 'en' => $criterion['en']],
                    'weight' => $criterion['weight'],
                    'sort_order' => $order + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function matrix(): array
    {
        $evaluate = [
            'call_evaluations.viewAny',
            'call_evaluations.view',
            'call_evaluations.create',
            'call_evaluations.update',
            'call_evaluations.delete',
            'call_evaluations.discuss',
            'call_exclusions.create',
        ];

        return [
            'admin' => self::PERMISSIONS,
            'super-admin' => self::PERMISSIONS,
            'crm_manager' => self::PERMISSIONS,
            'quality_auditor' => [...$evaluate, 'call_evaluations.view_all'],
            'department_manager' => $evaluate,
            'team_lead' => $evaluate,
            'sales_agent' => ['call_evaluations.viewAny', 'call_evaluations.view'],
            'support_agent' => ['call_evaluations.viewAny', 'call_evaluations.view'],
        ];
    }
}
