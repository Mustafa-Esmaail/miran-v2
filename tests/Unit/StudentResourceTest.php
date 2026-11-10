<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\Students\StudentResource;
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

class StudentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_students_with_enrollments(): void
    {
        $student = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createEnrollmentFor($student);

        $ids = StudentResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($ids->contains($student->id));
        $this->assertFalse($ids->contains($otherUser->id));
    }

    public function test_it_requires_student_permission_to_view_any(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $this->assertFalse(StudentResource::canViewAny());

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        Permission::query()->create([
            'name' => 'students.viewAny',
            'guard_name' => 'admin',
        ]);

        $admin->assignRole($role);
        $admin->givePermissionTo('students.viewAny');

        $this->assertTrue(StudentResource::canViewAny());
    }

    public function test_it_allows_users_permission_to_view_any(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $role = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);
        Permission::query()->create([
            'name' => 'users.viewAny',
            'guard_name' => 'admin',
        ]);

        $admin->assignRole($role);
        $admin->givePermissionTo('users.viewAny');

        $this->assertTrue(StudentResource::canViewAny());
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
            'status' => 'draft',
        ]);

        $courseGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        Role::findOrCreate('student', 'web');
        $user->assignRole('student');

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
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
