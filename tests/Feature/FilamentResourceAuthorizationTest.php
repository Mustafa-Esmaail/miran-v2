<?php

namespace Tests\Feature;

use App\Models\AssignmentRule;
use App\Models\BankTransferReceipt;
use App\Models\BroadcastMail;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\Team;
use App\Models\WorkflowRule;
use Database\Seeders\PermissionSeeder;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_filament_resource_model_has_a_registered_policy(): void
    {
        foreach ($this->filamentResourceModels() as $modelClass) {
            $this->assertNotNull(
                Gate::getPolicyFor($modelClass),
                "Missing policy mapping for model [{$modelClass}]."
            );
        }
    }

    public function test_permission_seeder_creates_permissions_for_filament_resource_models(): void
    {
        $this->seed(PermissionSeeder::class);

        $actions = ['viewAny', 'view', 'create', 'update', 'delete'];

        foreach ($this->filamentResourceModels() as $modelClass) {
            $resourceKey = $this->permissionResourceKeyForModel($modelClass);

            foreach ($actions as $action) {
                $this->assertDatabaseHas('permissions', [
                    'name' => "{$resourceKey}.{$action}",
                    'guard_name' => 'admin',
                ]);
            }
        }

        foreach (['students', 'trainers'] as $resourceKey) {
            foreach ($actions as $action) {
                $this->assertDatabaseHas('permissions', [
                    'name' => "{$resourceKey}.{$action}",
                    'guard_name' => 'admin',
                ]);
            }
        }

        $this->assertSame(
            Permission::query()->where('guard_name', 'admin')->count(),
            Permission::query()->where('guard_name', 'admin')->distinct('name')->count('name')
        );
    }

    public function test_crm_models_have_registered_policies_and_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $crmModels = [
            Department::class => 'departments',
            Team::class => 'teams',
            Employee::class => 'employees',
            ServiceRequest::class => 'service_requests',
            ServiceRequestActivity::class => 'service_request_activities',
            Deal::class => 'deals',
            CrmTask::class => 'crm_tasks',
            CrmActivity::class => 'crm_activities',
            AssignmentRule::class => 'assignment_rules',
            WorkflowRule::class => 'workflow_rules',
        ];

        $actions = ['viewAny', 'view', 'create', 'update', 'delete'];

        foreach ($crmModels as $modelClass => $resourceKey) {
            $this->assertNotNull(
                Gate::getPolicyFor($modelClass),
                "Missing CRM policy mapping for model [{$modelClass}]."
            );

            foreach ($actions as $action) {
                $this->assertDatabaseHas('permissions', [
                    'name' => "{$resourceKey}.{$action}",
                    'guard_name' => 'admin',
                ]);
            }
        }
    }

    public function test_permission_seeder_creates_permissions_for_recent_lms_modules(): void
    {
        $this->seed(PermissionSeeder::class);

        $actions = ['viewAny', 'view', 'create', 'update', 'delete'];
        $modules = [
            'course_curriculum_sections',
            'quizzes',
            'quiz_questions',
            'quiz_attempts',
            'departments',
            'teams',
            'employees',
            'service_requests',
            'service_request_activities',
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $this->assertDatabaseHas('permissions', [
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'admin',
                ]);
            }
        }
    }

    public function test_permission_seeder_creates_zoom_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        foreach (['zoom.create_meetings', 'zoom.sync_attendance', 'zoom.sync_recordings'] as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }
    }

    public function test_permission_seeder_assigns_all_permissions_to_super_admin_and_lms_permissions_to_instructor_roles(): void
    {
        $this->seed(PermissionSeeder::class);

        $allAdminPermissionNames = Permission::query()
            ->where('guard_name', 'admin')
            ->pluck('name')
            ->sort()
            ->values();

        $superAdminPermissionNames = Role::query()
            ->where('name', 'super-admin')
            ->where('guard_name', 'admin')
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values();

        $this->assertSame($allAdminPermissionNames->all(), $superAdminPermissionNames->all());

        // Instructor is intentionally read-only on sessions, plus normal chat
        // use (chat send/reply is enforced by ChatRoomPolicy, not a permission).
        $expectedPermissionNames = collect([
            'chat_rooms.view',
            'chat_rooms.viewAny',
            'sessions.view',
            'sessions.viewAny',
        ])->sort()->values();

        $rolePermissionNames = Role::query()
            ->where('name', 'instructor')
            ->where('guard_name', 'admin')
            ->firstOrFail()
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values();

        $this->assertSame($expectedPermissionNames->all(), $rolePermissionNames->all());
        $this->assertDatabaseMissing('roles', [
            'name' => 'trainer',
            'guard_name' => 'admin',
        ]);
    }

    /**
     * @return array<int, class-string>
     */
    private function filamentResourceModels(): array
    {
        return collect(File::allFiles(app_path('Filament/Admin/Resources')))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), 'Resource.php'))
            ->map(function ($file): ?string {
                $relativePath = str_replace(app_path(), '', $file->getPathname());
                $relativePath = ltrim(str_replace(['/', '\\'], '\\', $relativePath), '\\');
                $class = 'App\\'.Str::replaceLast('.php', '', $relativePath);

                if (! class_exists($class) || ! is_subclass_of($class, FilamentResource::class)) {
                    return null;
                }

                return $class::getModel();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function permissionResourceKeyForModel(string $modelClass): string
    {
        $aliases = [
            BankTransferReceipt::class => 'bank_receipts',
            BroadcastMail::class => 'send_mail',
            CourseAttachment::class => 'attachments',
            CourseGroupSession::class => 'sessions',
            MenuItem::class => 'menus',
        ];

        if (array_key_exists($modelClass, $aliases)) {
            return $aliases[$modelClass];
        }

        return (new $modelClass)->getTable();
    }
}
