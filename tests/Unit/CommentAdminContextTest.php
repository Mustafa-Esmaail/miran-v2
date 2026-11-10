<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentAdminContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_admin_context_for_session_comments(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Leadership Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Morning Group'],
            'status' => 'open',
        ]);

        $section = \App\Models\CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $section->id,
            'title' => 'Kickoff Session',
            'start_at' => now()->addHour(),
            'duration_minutes' => 60,
        ]);

        $comment = Comment::query()->create([
            'commentable_id' => $session->id,
            'commentable_type' => CourseGroupSession::class,
            'user_id' => $user->id,
            'content' => 'Important question',
            'approved' => true,
            'hidden' => false,
        ]);

        $context = Comment::applyAdminContextEagerLoading(Comment::query())
            ->findOrFail($comment->id)
            ->adminContext();

        $this->assertSame('Leadership Course', $context['course_title']);
        $this->assertSame('Morning Group', $context['group_title']);
        $this->assertSame('Session', $context['related_type']);
        $this->assertSame('Kickoff Session', $context['related_title']);
    }
}
