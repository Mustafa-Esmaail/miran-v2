<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentImportTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_student_import_template(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        $permission = Permission::query()->create([
            'name' => 'students.viewAny',
            'guard_name' => 'admin',
        ]);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.students.import-template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertDownload('student-import-template.xlsx');
        $this->assertSame(
            public_path('templates/student-import-template.xlsx'),
            $response->baseResponse->getFile()->getPathname()
        );
    }
}
