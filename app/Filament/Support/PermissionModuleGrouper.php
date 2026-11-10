<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\BankTransferReceipt;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\MenuItem;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Groups this app's "{module}.{action}" permissions (seeded by
 * database/seeders/PermissionSeeder.php) by the navigation group of the
 * Filament resource that owns each module, so the Role form/table can
 * present permissions the same way AdminPanelProvider organizes navigation.
 */
final class PermissionModuleGrouper
{
    /**
     * Mirrors the resource=>module aliases in database/seeders/PermissionSeeder.php
     * for models whose permission module differs from their table name.
     *
     * @var array<class-string, string>
     */
    private const LEGACY_MODULE_ALIASES = [
        BankTransferReceipt::class => 'bank_receipts',
        CourseAttachment::class => 'attachments',
        CourseGroupSession::class => 'sessions',
        MenuItem::class => 'menus',
    ];

    /**
     * Keys of resources/lang/{locale}/admin.php's "nav_groups" array, in display order.
     *
     * @var array<int, string>
     */
    private const GROUP_KEY_ORDER = [
        'lms_management',
        'commerce',
        'users_access',
        'cms',
        'settings',
        'certificates',
        'support',
        'crm',
        'other',
    ];

    /**
     * @return array<int, string>
     */
    public static function groupKeyOrder(): array
    {
        return self::GROUP_KEY_ORDER;
    }

    public static function groupLabel(string $groupKey): string
    {
        return __('admin.nav_groups.'.$groupKey);
    }

    public static function moduleLabel(string $module): string
    {
        return Str::headline($module);
    }

    public static function actionLabel(string $action): string
    {
        return Str::headline($action);
    }

    public static function groupKeyForModule(string $module): string
    {
        return self::moduleGroupKeys()[$module] ?? 'other';
    }

    /**
     * Map each permission module prefix to a stable navigation-group key, by
     * matching each Filament resource's table name (or legacy alias) against
     * its own navigation group.
     *
     * @return array<string, string>
     */
    public static function moduleGroupKeys(): array
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null) {
            return [];
        }

        $map = [];

        foreach ($panel->getResources() as $resourceClass) {
            $model = $resourceClass::getModel();

            if (! class_exists($model)) {
                continue;
            }

            $module = self::LEGACY_MODULE_ALIASES[$model] ?? (new $model)->getTable();
            $navigationGroup = $resourceClass::getNavigationGroup();
            $label = filled($navigationGroup) ? (string) $navigationGroup : __('admin.nav_groups.other');

            $map[$module] = self::groupKeyFromLabel($label);
        }

        return $map;
    }

    private static function groupKeyFromLabel(string $label): string
    {
        foreach (self::GROUP_KEY_ORDER as $key) {
            if (__('admin.nav_groups.'.$key) === $label) {
                return $key;
            }
        }

        return 'other';
    }

    /**
     * All "admin"-guard permissions, grouped by navigation group then module.
     *
     * @return array<string, array<string, array<string, string>>> groupKey => [module => [permission => actionLabel]]
     */
    public static function groupedPermissions(): array
    {
        $moduleGroupKeys = self::moduleGroupKeys();

        /** @var array<string, array<string, array<string, string>>> $grouped */
        $grouped = [];

        foreach (Permission::query()->where('guard_name', 'admin')->pluck('name') as $name) {
            $module = Str::before($name, '.');
            $action = Str::after($name, '.');
            $groupKey = $moduleGroupKeys[$module] ?? 'other';

            $grouped[$groupKey][$module][$name] = self::actionLabel($action);
        }

        uksort(
            $grouped,
            fn (string $a, string $b): int => array_search($a, self::GROUP_KEY_ORDER, true) <=> array_search($b, self::GROUP_KEY_ORDER, true)
        );

        foreach ($grouped as &$modules) {
            ksort($modules);
        }

        return $grouped;
    }

    /**
     * @return array<int, string> every "permissions_{module}" field name
     */
    public static function allFieldNames(): array
    {
        $fieldNames = [];

        foreach (self::groupedPermissions() as $modules) {
            foreach (array_keys($modules) as $module) {
                $fieldNames[] = 'permissions_'.$module;
            }
        }

        return $fieldNames;
    }
}
