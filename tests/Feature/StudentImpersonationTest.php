<?php

namespace Tests\Feature;

use App\Enums\ImpersonationEventType;
use App\Livewire\DashboardProfile;
use App\Models\User;
use Database\Seeders\StudentImpersonationPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_permissions_for_admin_guard_roles(): void
    {
        Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Role::query()->create(['name' => 'super-admin', 'guard_name' => 'admin']);
        Role::query()->create(['name' => 'management', 'guard_name' => 'admin']);

        $this->seed(StudentImpersonationPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'students.impersonate', 'guard_name' => 'admin']);
        $this->assertDatabaseHas('permissions', ['name' => 'impersonation_logs.viewAny', 'guard_name' => 'admin']);
    }

    public function test_plain_student_can_be_impersonated_but_staff_user_cannot(): void
    {
        $student = User::factory()->create();
        $staff = User::factory()->create();
        $staff->assignRole(Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']));

        $this->assertTrue($student->canBeImpersonated());
        $this->assertFalse($staff->canBeImpersonated());
    }

    public function test_admin_can_take_and_leave_impersonation_and_activity_is_logged(): void
    {
        $admin = User::factory()->create();
        $student = User::factory()->create();

        $this->actingAs($admin, 'admin');

        Permission::query()->create(['name' => 'students.impersonate', 'guard_name' => 'admin']);
        $admin->givePermissionTo('students.impersonate');

        $this->get(route('impersonate', ['id' => $student->id, 'guardName' => 'web']))
            ->assertRedirect();

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertSame($student->id, Auth::guard('web')->id());
        $this->assertFalse(Auth::guard('admin')->check());

        $this->assertDatabaseHas('impersonation_logs', [
            'admin_id' => $admin->id,
            'student_id' => $student->id,
            'event_type' => ImpersonationEventType::Started->value,
        ]);

        $this->get(route('dashboard'));

        $this->assertDatabaseHas('impersonation_logs', [
            'admin_id' => $admin->id,
            'student_id' => $student->id,
            'event_type' => ImpersonationEventType::RouteVisited->value,
            'route_name' => 'dashboard',
        ]);

        $this->get(route('impersonate.leave'))
            ->assertRedirect();

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertSame($admin->id, Auth::guard('admin')->id());
        $this->assertFalse(Auth::guard('web')->check());

        $this->assertDatabaseHas('impersonation_logs', [
            'admin_id' => $admin->id,
            'student_id' => $student->id,
            'event_type' => ImpersonationEventType::Ended->value,
        ]);
    }

    public function test_profile_save_is_blocked_while_impersonating(): void
    {
        $student = User::factory()->create(['name' => 'Original Name']);
        $admin = User::factory()->create();

        session(['impersonated_by' => $admin->id]);

        Livewire::actingAs($student)
            ->test(DashboardProfile::class)
            ->set('name', 'Changed Name')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Original Name',
        ]);
    }
}
