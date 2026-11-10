<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardCoursePlayerTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_course_player_main_page_lists_days_as_cards(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $firstSection = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'description' => ['en' => 'Course kickoff day'],
            'sort_order' => 1,
        ]);
        $secondSection = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'description' => ['en' => 'Advanced practice'],
            'sort_order' => 2,
        ]);
        Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstSection->id,
            'title' => ['en' => 'Introduction lesson'],
            'slug' => 'lesson-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson content</p>',
            'sort_order' => 1,
        ]);
        Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $secondSection->id,
            'title' => ['en' => 'Locked lesson'],
            'slug' => 'locked-lesson-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Locked lesson content</p>',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player', [$course->slug, $group->id]))
            ->assertOk()
            ->assertSee(__('web.course_player.curriculum'))
            ->assertSee(__('web.course_player.overview'))
            ->assertSee(__('web.course_player.progress'))
            ->assertSee('Day 1')
            ->assertSee('Course kickoff day')
            ->assertSee('Day 2')
            ->assertSee(__('web.course_player.state_locked'))
            ->assertSee(__('web.course_player.locked_day_hint'))
            ->assertSee(__('web.course_player.section_summary', ['count' => 1]))
            ->assertSee('xl:grid-cols-4', false)
            ->assertSee('Introduction lesson')
            ->assertDontSee(__('web.comments.tab'))
            ->assertDontSee(__('web.questions.tab'));
    }

    public function test_dashboard_course_player_day_page_lists_day_content_cards(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $section = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'sort_order' => 2,
        ]);

        Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $section->id,
            'title' => ['en' => 'Workshop lesson'],
            'slug' => 'lesson-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson content</p>',
            'sort_order' => 1,
        ]);

        CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $section->id,
            'title' => 'Live session',
            'start_at' => now()->addDay(),
            'duration_minutes' => 60,
        ]);

        \App\Models\CourseAttachment::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $section->id,
            'title' => ['en' => 'Slides deck'],
            'file_path' => 'course-intros/slides.pdf',
            'visibility' => 'enrolled_only',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.day', [$course->slug, $group->id, $section->id]))
            ->assertOk()
            ->assertSee('Day 2')
            ->assertSee('Workshop lesson')
            ->assertSee('Live session')
            ->assertSee('Slides deck')
            ->assertSee('xl:grid-cols-4', false)
            ->assertSee(__('web.course_player.open_resource'));
    }

    public function test_dashboard_course_player_shows_empty_state_when_group_has_no_days(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $otherGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Other Group'],
            'status' => 'open',
        ]);

        CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $otherGroup->id,
            'title' => ['en' => 'Other Group Day 1'],
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player', [$course->slug, $group->id]))
            ->assertOk()
            ->assertSee(__('web.course_player.no_curriculum'))
            ->assertDontSee('Other Group Day 1');
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
            'status' => 'draft',
        ]);

        $courseGroup = CourseGroup::query()->create([
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

        return ['course' => $course, 'group' => $courseGroup];
    }
}
