<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Support\PermissionModuleGrouper;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_resource_form_groups_permissions_by_module(): void
    {
        $contents = file_get_contents(base_path('app/Filament/Admin/Resources/Roles/Schemas/RoleForm.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('PermissionModuleGrouper::groupedPermissions()', $contents);
        $this->assertStringContainsString("CheckboxList::make('permissions_'.\$module)", $contents);
        $this->assertStringNotContainsString("Select::make('permissions')", $contents);
    }

    public function test_permission_module_grouper_groups_known_permissions_by_navigation_group(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Permission::query()->create(['name' => 'categories.create', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'blogs.create', 'guard_name' => 'admin']);

        $grouped = PermissionModuleGrouper::groupedPermissions();

        $this->assertArrayHasKey('categories', $grouped['lms_management'] ?? []);
        $this->assertArrayHasKey('blogs', $grouped['cms'] ?? []);
    }

    public function test_role_create_and_edit_syncs_selected_module_permissions(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);

        Permission::query()->create(['name' => 'categories.create', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'categories.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'blogs.create', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.create', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.update', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.delete', 'guard_name' => 'admin']);

        $adminRole->givePermissionTo([
            'categories.create', 'categories.viewAny', 'blogs.create',
            'roles.viewAny', 'roles.create', 'roles.update', 'roles.delete',
        ]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'editors', 'guard_name' => 'admin']);
        $role->syncPermissions(['categories.create', 'blogs.create']);

        $response = $this->get('/admin/roles/'.$role->getKey().'/edit');
        $response->assertOk();
        $response->assertSee('categories.create', false);
        $response->assertSee('blogs.create', false);
    }

    public function test_saving_a_role_does_not_fail_uniqueness_against_a_same_named_role_on_another_guard(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);

        Permission::query()->create(['name' => 'roles.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.update', 'guard_name' => 'admin']);
        $adminRole->givePermissionTo(['roles.viewAny', 'roles.update']);
        $admin->assignRole($adminRole);

        $this->actingAs($admin, 'admin');

        // Same role name exists under a different guard (e.g. created by the
        // checkout/enrollment flow via Role::findOrCreate('student', 'web')).
        Role::findOrCreate('student', 'web');
        $studentAdminRole = Role::query()->firstOrCreate(['name' => 'student', 'guard_name' => 'admin']);

        Livewire::test(EditRole::class, ['record' => $studentAdminRole->getKey()])
            ->fillForm(['name' => 'student'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_saving_a_role_still_fails_uniqueness_against_a_same_named_role_on_the_same_guard(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);

        Permission::query()->create(['name' => 'roles.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.update', 'guard_name' => 'admin']);
        $adminRole->givePermissionTo(['roles.viewAny', 'roles.update']);
        $admin->assignRole($adminRole);

        $this->actingAs($admin, 'admin');

        Role::query()->create(['name' => 'sales_agent', 'guard_name' => 'admin']);
        $editorsRole = Role::query()->create(['name' => 'editors', 'guard_name' => 'admin']);

        Livewire::test(EditRole::class, ['record' => $editorsRole->getKey()])
            ->fillForm(['name' => 'sales_agent'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_super_admin_role_edit_page_redirects_away_for_non_super_admins(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create();
        $adminRole = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);

        Permission::query()->create(['name' => 'roles.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.update', 'guard_name' => 'admin']);
        $adminRole->givePermissionTo(['roles.viewAny', 'roles.update']);
        $admin->assignRole($adminRole);

        $this->actingAs($admin, 'admin');

        $superAdminRole = Role::query()->create(['name' => 'super-admin', 'guard_name' => 'admin']);

        $response = $this->get('/admin/roles/'.$superAdminRole->getKey().'/edit');
        $response->assertRedirect('/admin/roles');
    }

    public function test_super_admin_role_edit_page_is_accessible_to_super_admins(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $superAdminUser = User::factory()->create();

        Permission::query()->create(['name' => 'roles.viewAny', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'roles.update', 'guard_name' => 'admin']);
        $superAdminRole = Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $superAdminRole->givePermissionTo(['roles.viewAny', 'roles.update']);
        $superAdminUser->assignRole($superAdminRole);

        $this->actingAs($superAdminUser, 'admin');

        $response = $this->get('/admin/roles/'.$superAdminRole->getKey().'/edit');
        $response->assertOk();
    }
}
