<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseGroupImportTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_course_group_import_template(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        $permission = Permission::query()->create([
            'name' => 'course_groups.viewAny',
            'guard_name' => 'admin',
        ]);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.course-groups.import-template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('course-group-import-template.csv');
        $this->assertSame(
            public_path('templates/course-group-import-template.csv'),
            $response->baseResponse->getFile()->getPathname()
        );
    }
}
