<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LmsCmsRolePermissionSeeder extends Seeder
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
        $this->command->info('LMS/CMS role permissions seeded.');
    }

    /**
     * Permission matrix for LMS and CMS roles.
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
            // ─── course_manager ──────────────────────────────────────────────
            // Full LMS control without system-level admin access
            'course_manager' => [
                ...$perms('courses', $all),
                ...$perms('course_groups', $all),
                ...$perms('sessions', $all),
                ...$perms('enrollments', $all),
                ...$perms('certificates', $all),
                ...$perms('attachments', $all),
                ...$perms('course_curriculum_sections', $all),
                ...$perms('lessons', $all),
                ...$perms('quizzes', $all),
                ...$perms('quiz_questions', $all),
                ...$perms('quiz_attempts', $read),
                ...$perms('course_reviews', $all),
                ...$perms('course_questions', $all),
                ...$perms('question_answers', $all),
                ...$perms('categories', $all),
                ...$perms('students', $read),
                ...$perms('trainers', $read),
                ...$perms('discounts', $all),
                ...$perms('bundles', $all),
                'zoom.create_meetings',
                'zoom.sync_attendance',
                'zoom.sync_recordings',
                'zoom.view_live_attendance',
            ],

            // ─── training_coordinator ────────────────────────────────────────
            // Operational: manages groups/sessions/seats; read-only on content
            'training_coordinator' => [
                ...$perms('courses', $read),
                ...$perms('course_groups', $all),
                ...$perms('sessions', $all),
                ...$perms('enrollments', $all),
                ...$perms('certificates', $read),
                ...$perms('attachments', $read),
                ...$perms('course_curriculum_sections', $read),
                ...$perms('lessons', $read),
                ...$perms('students', $read),
                ...$perms('trainers', $read),
                'zoom.create_meetings',
                'zoom.sync_attendance',
                'zoom.sync_recordings',
                'zoom.view_live_attendance',
            ],

            // ─── content_editor ──────────────────────────────────────────────
            // Creates/edits CMS content; cannot delete or manage menus structure
            'content_editor' => [
                ...$perms('pages', $write),
                ...$perms('sections', $write),
                ...$perms('blogs', $write),
                ...$perms('blog_categories', $write),
                ...$perms('announcements', $write),
                ...$perms('menus', $read),
                ...$perms('menus', $read),
            ],

            // ─── content_manager ─────────────────────────────────────────────
            // Full CMS control: pages, menus, blog, announcements
            'content_manager' => [
                ...$perms('pages', $all),
                ...$perms('sections', $all),
                ...$perms('blogs', $all),
                ...$perms('blog_categories', $all),
                ...$perms('blog_comments', $all),
                ...$perms('announcements', $all),
                ...$perms('menus', $all),
            ],
        ];
    }
}
