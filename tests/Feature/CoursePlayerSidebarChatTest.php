<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoursePlayerSidebarChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_player_renders_inline_sidebar_chat_above_dashboard_nav(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $student = User::factory()->create();
        $trainer = User::factory()->create();

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course Title'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-title',
            'status' => 'published',
        ]);

        $course->trainers()->attach($trainer->id);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 100,
            'status' => 'open',
        ]);

        CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'description' => ['en' => 'Day summary'],
            'sort_order' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ]);

        $response = $this
            ->actingAs($student, 'web')
            ->get(route('dashboard.course-player', [$course->slug, $group->id]));

        $response->assertOk();
        $response->assertSee(__('web.chat.title'));
        $response->assertSee(route('dashboard.messages'));

        Livewire::actingAs($student, 'web')
            ->test(\App\Livewire\Chat\CourseSidebar::class, ['courseGroupId' => $group->id])
            ->assertSet('activeRoomId', app(ChatService::class)->getRoomsForUser($student)->first()?->id)
            ->assertSee(__('web.chat.open'));
    }
}
