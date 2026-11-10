<?php

namespace Tests\Feature;

use App\Livewire\CourseProgressBar;
use App\Livewire\DashboardCoursePlayer;
use App\Models\Category;
use App\Models\ContentProgress;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCourseProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_reaches_100_when_all_items_viewed(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => 'active',
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Lesson'],
            'slug' => Str::slug(Str::random(10)),
            'type' => 'text',
            'content' => 'Lesson content',
            'is_preview' => false,
            'sort_order' => 1,
        ]);

        $attachment = CourseAttachment::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Attachment'],
            'file_path' => 'file.pdf',
            'visibility' => 'public',
        ]);

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'title' => 'Session',
            'start_at' => now(),
            'duration_minutes' => 60,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->call('markContentViewed', 'lesson', $lesson->id)
            ->call('markContentViewed', 'lesson', $lesson->id)
            ->call('markContentViewed', 'attachment', $attachment->id)
            ->call('markContentViewed', 'session', $session->id);

        $this->assertSame(3, ContentProgress::query()->count());

        Livewire::actingAs($user)
            ->test(CourseProgressBar::class, ['courseSlug' => $course->slug])
            ->assertViewHas('progress', 100);
    }

    public function test_progress_bar_counts_quizzes_in_total_content(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => 'active',
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Lesson'],
            'slug' => Str::slug(Str::random(10)),
            'type' => 'text',
            'content' => 'Lesson content',
            'is_preview' => false,
            'sort_order' => 1,
        ]);

        CourseAttachment::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Attachment'],
            'file_path' => 'file.pdf',
            'visibility' => 'public',
        ]);

        CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'title' => 'Session',
            'start_at' => now(),
            'duration_minutes' => 60,
        ]);

        Quiz::query()->create([
            'course_id' => $course->id,
            'title' => 'Quiz',
            'passing_score' => 1,
        ]);

        ContentProgress::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'contentable_type' => Lesson::class,
            'contentable_id' => $lesson->id,
        ]);

        Livewire::actingAs($user)
            ->test(CourseProgressBar::class, ['courseSlug' => $course->slug])
            ->assertViewHas('progress', 25);
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => 'published',
        ]);
    }
}
