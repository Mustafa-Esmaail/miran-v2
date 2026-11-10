<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Admin\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\StudentCreationService;
use Filament\Actions\RestoreAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentSoftDeleteRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(bool $withAdminRole = true): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $permissions = ['enrollments.viewAny', 'enrollments.view', 'enrollments.create', 'enrollments.update', 'enrollments.delete', 'courses.viewAny', 'course_groups.viewAny'];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        if ($withAdminRole) {
            $admin->assignRole(Role::findOrCreate('admin', 'admin'));
        }

        return $admin;
    }

    private function makeGroup(string $slug): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => $slug.'-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);
    }

    private function makeTrashedEnrollment(User $student, CourseGroup $group, EnrollmentStatus $status = EnrollmentStatus::Active): Enrollment
    {
        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => $status,
            'activated_at' => now(),
        ]);
        $enrollment->delete();

        return $enrollment;
    }

    private function makeOrderItem(User $student, CourseGroup $group): OrderItem
    {
        $order = Order::query()->create([
            'user_id' => $student->id,
            'order_no' => 'ORD-RESTORE-'.random_int(100000, 999999),
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group',
            'price_amount' => 100,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);
    }

    public function test_admin_create_restores_soft_deleted_enrollment_instead_of_failing(): void
    {
        $this->actingAsAdmin();
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-create');
        $trashed = $this->makeTrashedEnrollment($student, $group, EnrollmentStatus::Cancelled);

        Livewire::test(CreateEnrollment::class)
            ->fillForm([
                'user_id' => $student->id,
                'course_id' => $group->course_id,
                'course_group_id' => $group->id,
                'status' => EnrollmentStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Enrollment::withTrashed()->where('user_id', $student->id)->where('course_group_id', $group->id)->count());

        $restored = Enrollment::query()->findOrFail($trashed->id);
        $this->assertSame(EnrollmentStatus::Active, $restored->status);
        $this->assertNull($restored->deleted_by);
    }

    public function test_admin_create_shows_validation_error_when_active_enrollment_exists(): void
    {
        $this->actingAsAdmin();
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-duplicate');

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Active,
        ]);

        Livewire::test(CreateEnrollment::class)
            ->fillForm([
                'user_id' => $student->id,
                'course_id' => $group->course_id,
                'course_group_id' => $group->id,
                'status' => EnrollmentStatus::Active->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['course_group_id' => 'unique']);

        $this->assertSame(1, Enrollment::withTrashed()->where('user_id', $student->id)->count());
    }

    public function test_same_group_is_allowed_for_a_different_trainee(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup('restore-other-user');

        Enrollment::query()->create([
            'user_id' => User::factory()->create()->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Active,
        ]);

        $student = User::factory()->create();

        Livewire::test(CreateEnrollment::class)
            ->fillForm([
                'user_id' => $student->id,
                'course_id' => $group->course_id,
                'course_group_id' => $group->id,
                'status' => EnrollmentStatus::Active->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Enrollment::query()->where('user_id', $student->id)->where('course_group_id', $group->id)->exists());
    }

    public function test_admin_can_restore_soft_deleted_enrollment_from_table(): void
    {
        $this->actingAsAdmin();
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-table');
        $trashed = $this->makeTrashedEnrollment($student, $group);

        Livewire::test(ListEnrollments::class)
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$trashed])
            ->callAction(TestAction::make(RestoreAction::class)->table($trashed));

        $this->assertNull($trashed->fresh()->deleted_at);
    }

    public function test_restore_action_is_hidden_for_non_admin_roles(): void
    {
        $this->actingAsAdmin(withAdminRole: false);
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-hidden');
        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        Livewire::test(ListEnrollments::class)
            ->assertActionHidden(TestAction::make(RestoreAction::class)->table($enrollment));
    }

    public function test_checkout_enrollment_restores_soft_deleted_row(): void
    {
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-checkout');
        $trashed = $this->makeTrashedEnrollment($student, $group, EnrollmentStatus::Cancelled);
        $item = $this->makeOrderItem($student, $group);

        $enrollment = app(EnrollmentRepositoryInterface::class)->createForCourseGroup($item, $group, $student->id);

        $this->assertSame($trashed->id, $enrollment->id);
        $this->assertNull($enrollment->fresh()->deleted_at);
        $this->assertSame($item->id, $enrollment->fresh()->order_item_id);
        $this->assertSame(EnrollmentStatus::Active, $enrollment->fresh()->status);
    }

    public function test_order_item_enrollment_restores_soft_deleted_row(): void
    {
        $student = User::factory()->create();
        $group = $this->makeGroup('restore-order-item');
        $trashed = $this->makeTrashedEnrollment($student, $group);
        $item = $this->makeOrderItem($student, $group);

        $enrollment = app(EnrollmentRepositoryInterface::class)->createForOrderItem($item, $student->id);

        $this->assertSame($trashed->id, $enrollment->id);
        $this->assertSame($item->id, $enrollment->fresh()->order_item_id);
        $this->assertSame(1, Enrollment::withTrashed()->where('user_id', $student->id)->count());
    }

    public function test_student_create_or_enroll_restores_soft_deleted_row(): void
    {
        Role::findOrCreate('student', 'web');
        $student = User::factory()->create(['email' => 'restore-student@example.com']);
        $group = $this->makeGroup('restore-student-service');
        $trashed = $this->makeTrashedEnrollment($student, $group);

        $result = app(StudentCreationService::class)->createOrEnroll([
            'name' => 'Student',
            'email' => 'restore-student@example.com',
        ], $group->course_id, $group->id);

        $this->assertTrue($result['enrolled']);
        $this->assertNull($trashed->fresh()->deleted_at);
        $this->assertSame(1, Enrollment::withTrashed()->where('user_id', $student->id)->count());
    }
}
