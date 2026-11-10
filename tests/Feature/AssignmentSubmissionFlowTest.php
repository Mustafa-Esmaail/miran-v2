<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\CoursePlayer\Assignment as DashboardCoursePlayerAssignment;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_an_assignment_once(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $assignment = Assignment::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $day->id,
            'title' => ['en' => 'Assignment 1'],
            'description' => '<p>Do the thing.</p>',
            'max_grade' => 10,
            'is_published' => true,
        ]);

        $file = UploadedFile::fake()->create('homework.pdf', 100);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayerAssignment::class, ['course_slug' => $course->slug, 'group' => $group->id, 'assignment' => $assignment->id])
            ->set('newFile', $file)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, $assignment->submissions()->count());

        // A second submit attempt must not create a duplicate submission.
        Livewire::actingAs($user)
            ->test(DashboardCoursePlayerAssignment::class, ['course_slug' => $course->slug, 'group' => $group->id, 'assignment' => $assignment->id])
            ->set('newFile', UploadedFile::fake()->create('again.xlsx', 50))
            ->call('submit');

        $this->assertSame(1, $assignment->submissions()->count());
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $assignment = Assignment::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $day->id,
            'title' => ['en' => 'Assignment 1'],
            'description' => '<p>Do the thing.</p>',
            'max_grade' => 10,
            'is_published' => true,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayerAssignment::class, ['course_slug' => $course->slug, 'group' => $group->id, 'assignment' => $assignment->id])
            ->set('newFile', UploadedFile::fake()->create('malware.exe', 10))
            ->assertHasErrors(['newFile' => 'mimes']);

        $this->assertSame(0, $assignment->submissions()->count());
    }

    /**
     * @return array{course: Course, group: CourseGroup}
     */
    private function createEnrollmentFor(User $user): array
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

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

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
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course group',
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        return ['course' => $course, 'group' => $group];
    }
}
