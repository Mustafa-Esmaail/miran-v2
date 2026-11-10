<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TraineeRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_merges_existing_trainee_role_data_into_student(): void
    {
        $user = User::factory()->create();
        $traineeRole = Role::query()->firstOrCreate(['name' => 'trainee', 'guard_name' => 'web']);
        $studentRole = Role::query()->firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $permission = Permission::query()->firstOrCreate(['name' => 'courses.viewAny', 'guard_name' => 'web']);

        $traineeRole->givePermissionTo($permission);
        $user->assignRole($traineeRole);

        $migration = include database_path('migrations/2026_04_30_155152_rename_trainee_role_to_student.php');
        $migration->up();

        $this->assertDatabaseMissing('roles', [
            'name' => 'trainee',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $studentRole->id,
            'name' => 'student',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $studentRole->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $permission->id,
            'role_id' => $studentRole->id,
        ]);
    }

    public function test_it_creates_student_role_when_trainee_was_already_deleted(): void
    {
        $user = User::factory()->create();
        $this->createEnrollmentFor($user);

        Role::query()
            ->where('name', 'trainee')
            ->delete();

        Role::query()
            ->where('name', 'student')
            ->delete();

        $migration = include database_path('migrations/2026_04_30_155152_rename_trainee_role_to_student.php');
        $migration->up();

        $this->assertDatabaseHas('roles', [
            'name' => 'student',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'student',
            'guard_name' => 'admin',
        ]);

        $studentRoleId = Role::query()
            ->where('name', 'student')
            ->where('guard_name', 'web')
            ->value('id');

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $studentRoleId,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);
    }

    private function createEnrollmentFor(User $user): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);

        $courseGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $courseGroup->id,
            'title_snapshot' => 'Course group',
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $courseGroup->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);
    }
}
