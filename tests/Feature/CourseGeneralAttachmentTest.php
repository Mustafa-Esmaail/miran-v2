<?php

namespace Tests\Feature;

use App\Enums\AttachmentVisibility;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseGeneralAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_attachment_is_shown_in_the_player_of_every_group(): void
    {
        $course = $this->createCourse();
        $firstGroup = $this->createGroup($course, 'First Group');
        $secondGroup = $this->createGroup($course, 'Second Group');
        $firstStudent = User::factory()->create();
        $secondStudent = User::factory()->create();
        $this->enroll($firstStudent, $firstGroup);
        $this->enroll($secondStudent, $secondGroup);

        $this->createAttachment($course, 'Course handbook', AttachmentVisibility::General);

        foreach ([[$firstStudent, $firstGroup], [$secondStudent, $secondGroup]] as [$student, $group]) {
            $this->actingAs($student)
                ->get(route('dashboard.course-player', [$course->slug, $group->id]))
                ->assertOk()
                ->assertSee(__('web.course_player.general_attachments'))
                ->assertSee('Course handbook');
        }
    }

    public function test_general_section_is_hidden_when_course_has_no_general_attachments(): void
    {
        $course = $this->createCourse();
        $group = $this->createGroup($course, 'Group');
        $student = User::factory()->create();
        $this->enroll($student, $group);

        $section = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);
        $this->createAttachment($course, 'Day slides', AttachmentVisibility::EnrolledOnly, $section->id);

        $this->actingAs($student)
            ->get(route('dashboard.course-player', [$course->slug, $group->id]))
            ->assertOk()
            ->assertDontSee(__('web.course_player.general_attachments'));
    }

    public function test_general_attachments_of_another_course_are_not_shown(): void
    {
        $course = $this->createCourse();
        $group = $this->createGroup($course, 'Group');
        $student = User::factory()->create();
        $this->enroll($student, $group);

        $this->createAttachment($this->createCourse(), 'Other course handbook', AttachmentVisibility::General);

        $this->actingAs($student)
            ->get(route('dashboard.course-player', [$course->slug, $group->id]))
            ->assertOk()
            ->assertDontSee('Other course handbook');
    }

    public function test_student_not_enrolled_cannot_open_the_player(): void
    {
        $course = $this->createCourse();
        $group = $this->createGroup($course, 'Group');
        $this->createAttachment($course, 'Course handbook', AttachmentVisibility::General);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.course-player', [$course->slug, $group->id]))
            ->assertNotFound();
    }

    public function test_general_attachment_is_not_available_in_the_public_viewer(): void
    {
        $attachment = $this->createAttachment($this->createCourse(), 'Course handbook', AttachmentVisibility::General);

        $this->get(route('attachments.view', $attachment))->assertNotFound();
    }

    public function test_saving_a_general_attachment_clears_its_curriculum_section(): void
    {
        $course = $this->createCourse();
        $group = $this->createGroup($course, 'Group');
        $section = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $attachment = $this->createAttachment($course, 'Slides', AttachmentVisibility::EnrolledOnly, $section->id);
        $this->assertSame($section->id, $attachment->course_curriculum_section_id);

        $attachment->update(['visibility' => AttachmentVisibility::General]);

        $this->assertNull($attachment->fresh()->course_curriculum_section_id);
    }

    public function test_only_group_bound_visibilities_require_a_curriculum_section(): void
    {
        $this->assertTrue(AttachmentVisibility::sectionRequiredFor('public'));
        $this->assertTrue(AttachmentVisibility::sectionRequiredFor('enrolled_only'));
        $this->assertTrue(AttachmentVisibility::sectionRequiredFor(null));
        $this->assertFalse(AttachmentVisibility::sectionRequiredFor('profile'));
        $this->assertFalse(AttachmentVisibility::sectionRequiredFor('general'));
        $this->assertFalse(AttachmentVisibility::sectionRequiredFor(AttachmentVisibility::General));
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'draft',
        ]);
    }

    private function createGroup(Course $course, string $title): CourseGroup
    {
        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title],
            'status' => 'open',
        ]);
    }

    private function createAttachment(Course $course, string $title, AttachmentVisibility $visibility, ?int $sectionId = null): CourseAttachment
    {
        return CourseAttachment::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $sectionId,
            'title' => ['en' => $title],
            'file_path' => ['course-attachments/'.Str::slug($title).'.pdf'],
            'visibility' => $visibility,
        ]);
    }

    private function enroll(User $user, CourseGroup $group): void
    {
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
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);
    }
}
