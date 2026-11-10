<?php

namespace Database\Seeders;

use App\Models\BankTransferReceipt;
use App\Models\BroadcastMail;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $actions = ['viewAny', 'view', 'create', 'update', 'delete'];

    public function run(): void
    {
        // Ensure Spatie uses the right guard during this seeding run
        Config::set('auth.defaults.guard', 'admin');
        Config::set('permission.defaults.guard', 'admin');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Discovering resources and models...');
        $resources = $this->discoverResourcePermissions();
        $this->command->info('Found '.count($resources).' resources');

        $this->command->info('Creating permissions...');
        $permissionCount = 0;
        foreach ($resources as $resource) {
            foreach ($this->actions as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$resource}.{$action}", 'guard_name' => 'admin'],
                    []
                );
                $permissionCount++;
            }
        }

        foreach ($this->zoomPermissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                []
            );
            $permissionCount++;
        }

        foreach ($this->chatPermissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                []
            );
            $permissionCount++;
        }

        foreach ($this->commissionPermissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                []
            );
            $permissionCount++;
        }

        foreach ($this->pagePermissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                []
            );
            $permissionCount++;
        }

        $this->command->info("Created {$permissionCount} permissions");

        $this->pruneStalePermissions($resources);

        $adminPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->get();

        $this->syncRolePermissions('admin', $adminPermissions);
        $this->syncRolePermissions('super-admin', $adminPermissions);

        // Instructor: read-only access to sessions, plus normal chat use (chat
        // send/reply is enforced by ChatRoomPolicy, not by a permission check).
        $instructorPermissions = $adminPermissions
            ->filter(fn (Permission $permission): bool => in_array($permission->name, [
                'sessions.viewAny',
                'sessions.view',
                'chat_rooms.viewAny',
                'chat_rooms.view',
            ], true))
            ->values();

        $this->syncRolePermissions('instructor', $instructorPermissions);

        // Sales: commerce navigation group, read-only (no create/update/delete).
        $salesPermissions = $adminPermissions
            ->filter(fn (Permission $permission): bool => in_array($permission->name, [
                'orders.viewAny',
                'orders.view',
                'payments.viewAny',
                'payments.view',
                'bank_receipts.viewAny',
                'bank_receipts.view',
            ], true))
            ->values();

        $this->syncRolePermissions('sales', $salesPermissions);

        // Training: full access to LMS and CRM modules.
        $trainingPermissions = $adminPermissions
            ->filter(function (Permission $permission): bool {
                $module = explode('.', $permission->name)[0] ?? $permission->name;

                return in_array($module, $this->lmsModules(), true)
                    || in_array($module, $this->crmModules(), true);
            })
            ->values();

        $this->syncRolePermissions('training', $trainingPermissions);

        // Operation: everything except coupons and the report pages.
        $operationPermissions = $adminPermissions
            ->filter(function (Permission $permission): bool {
                $module = explode('.', $permission->name)[0] ?? $permission->name;

                if ($module === 'coupons') {
                    return false;
                }

                return ! in_array($permission->name, [
                    'orders_report.viewAny',
                    'crm_reports.viewAny',
                    'sales_performance_report.viewAny',
                    'order_line_items_report.viewAny',
                    'store_organic_orders_report.viewAny',
                ], true);
            })
            ->values();

        $this->syncRolePermissions('operation', $operationPermissions);

        // Management: everything except settings.
        $managementPermissions = $adminPermissions
            ->filter(function (Permission $permission): bool {
                $module = explode('.', $permission->name)[0] ?? $permission->name;

                return $module !== 'settings';
            })
            ->values();

        $this->syncRolePermissions('management', $managementPermissions);

        // Assign admin role to all users with admin@example.com or find existing admins
        $this->command->info('Assigning admin role to users...');
        $adminUser = User::query()->where('email', 'admin@example.com')->first();
        if ($adminUser) {
            $adminUser->syncRoles(['admin', 'super-admin']);
            $this->command->info("Assigned admin role to: {$adminUser->email}");
        } else {
            $this->command->warn('No user with email admin@example.com found');
        }

        // Also assign to first user if different
        $firstUser = User::query()->first();
        if ($firstUser && $firstUser->email !== 'admin@example.com') {
            $firstUser->syncRoles(['admin', 'super-admin']);
            $this->command->info("Assigned admin role to: {$firstUser->email}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Permission seeding completed!');
    }

    private function discoverResourcePermissions(): array
    {
        $resourceAliases = [
            BankTransferReceipt::class => 'bank_receipts',
            BroadcastMail::class => 'send_mail',
            CourseAttachment::class => 'attachments',
            CourseGroupSession::class => 'sessions',
            MenuItem::class => 'menus',
        ];
        $extraModules = [
            'students',
            'trainers',
            'course_curriculum_sections',
            'quizzes',
            'quiz_questions',
            'quiz_attempts',
            'departments',
            'teams',
            'employees',
            'service_requests',
            'service_request_activities',
            // Chat: auto-discovered from ChatRoom model, listed here for visibility.
            // Permissions: chat_rooms.viewAny, chat_rooms.view (create/update/delete
            // are blocked by ChatRoomPolicy regardless of permission state).
            'chat_rooms',
            // Spatie Role model has no Filament resource so it is not auto-discovered.
            // roles.* permissions must exist so RolePolicy can be evaluated.
            'roles',
            // Log Viewer page — checked via authorize() closure in AdminPanelProvider.
            // admin/super-admin roles get viewAny; no other roles need this.
            'log_viewer',
        ];

        // Discover all Filament resources
        $resourceModels = collect(File::allFiles(app_path('Filament/Admin/Resources')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Resource.php'))
            ->map(function ($file) {
                $relativePath = str_replace(app_path(), '', $file->getPathname());
                $relativePath = ltrim(str_replace(['/', '\\'], '\\', $relativePath), '\\');
                $class = 'App\\'.str_replace('.php', '', $relativePath);

                return class_exists($class) && is_subclass_of($class, FilamentResource::class)
                    ? $class::getModel()
                    : null;
            })
            ->filter()
            ->push(Setting::class)
            ->unique()
            ->values();

        // Also discover all models from app/Models directory
        $allModels = collect(File::files(app_path('Models')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.php'))
            ->map(function ($file) {
                $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

                if (class_exists($class) && is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                    return $class;
                }

                return null;
            })
            ->filter()
            ->merge($resourceModels)
            ->unique()
            ->values();

        $resources = $allModels
            ->map(function (string $model) use ($resourceAliases): string {
                if (array_key_exists($model, $resourceAliases)) {
                    return $resourceAliases[$model];
                }

                return (new $model)->getTable();
            })
            ->merge($extraModules)
            ->unique()
            ->sort()
            ->values();

        return $resources->all();
    }

    /**
     * Remove any "admin"-guard permission that no longer corresponds to a
     * discovered resource/module or a hand-listed extra permission, so
     * renamed/removed resources don't leave orphaned rows behind.
     *
     * @param  array<int, string>  $resources
     */
    private function pruneStalePermissions(array $resources): void
    {
        $expectedNames = collect($resources)
            ->merge($this->externallySeededModules())
            ->crossJoin($this->actions)
            ->map(fn (array $pair): string => "{$pair[0]}.{$pair[1]}")
            ->merge($this->zoomPermissions())
            ->merge($this->chatPermissions())
            ->merge($this->commissionPermissions())
            ->merge($this->pagePermissions())
            ->unique()
            ->values();

        $stale = Permission::query()
            ->where('guard_name', 'admin')
            ->whereNotIn('name', $expectedNames)
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        $this->command->info("Removing {$stale->count()} stale permissions...");
        Permission::query()->whereKey($stale->pluck('id'))->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Permission>  $permissions
     */
    private function syncRolePermissions(string $roleName, \Illuminate\Support\Collection $permissions): void
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'admin'],
            []
        );

        $role->syncPermissions($permissions);

        $this->command->info("Assigned {$permissions->count()} permissions to {$roleName} role");
    }

    /**
     * @return array<int, string>
     */
    private function lmsModules(): array
    {
        return [
            'announcements',
            'attachments',
            'bundles',
            'categories',
            'certificates',
            'chat_rooms',
            'comments',
            'course_curriculum_sections',
            'course_groups',
            'course_questions',
            'course_reviews',
            'courses',
            'discounts',
            'enrollments',
            'lessons',
            'question_answers',
            'quiz_attempts',
            'quiz_questions',
            'quizzes',
            'sessions',
            'students',
            'trainers',
            'attendance',
            'lms_dashboard',
        ];
    }

    /**
     * Modules under the "crm" navigation group (see PermissionModuleGrouper),
     * plus the CRM-only standalone pages.
     *
     * @return array<int, string>
     */
    private function crmModules(): array
    {
        return [
            'assignment_rules',
            'commission_ledgers',
            'companies',
            'crm_activities',
            'crm_tasks',
            'deals',
            'departments',
            'employees',
            'sales_targets',
            'service_request_types',
            'service_request_activities',
            'service_requests',
            'share_links',
            'teams',
            'workflow_rules',
            'crm_dashboard',
            'crm_reports',
        ];
    }

    /**
     * Modules whose permissions are created by a separate, dedicated seeder
     * (not created or role-synced here) but must still be protected from
     * pruneStalePermissions() once they exist.
     *
     * @return array<int, string>
     */
    private function externallySeededModules(): array
    {
        return [
            'complaint_service_requests',
            'b2b_service_requests',
            'b2c_service_requests',
            'employee_request_performance_report',
            'course_request_status_report',
            'service_request_status_report',
            'assignment_submissions',
            'landing_versions',
            'landing_version_report',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function zoomPermissions(): array
    {
        return [
            'zoom.create_meetings',
            'zoom.sync_attendance',
            'zoom.sync_recordings',
            'zoom.view_live_attendance',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function chatPermissions(): array
    {
        return [
            'users.chat_ban',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function commissionPermissions(): array
    {
        return [
            'commission_ledgers.approve',
            'commission_ledgers.mark_paid',
        ];
    }

    /**
     * Standalone Filament pages that are not backed by a CRUD resource, so
     * they need their own hand-listed permission checked via canAccess().
     *
     * @return array<int, string>
     */
    private function pagePermissions(): array
    {
        return [
            'orders_report.viewAny',
            'attendance.viewAny',
            'crm_dashboard.viewAny',
            'crm_reports.viewAny',
            'lms_dashboard.viewAny',
            'sales_performance_report.viewAny',
            'order_line_items_report.viewAny',
            'store_organic_orders_report.viewAny',
            'course_performance_report.viewAny',
        ];
    }
}
