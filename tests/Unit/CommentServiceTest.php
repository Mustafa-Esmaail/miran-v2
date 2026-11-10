<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_comment_and_reply_and_scopes_visibility(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $course = $this->createCourse();

        $service = $this->app->make(CommentService::class);

        $comment = $service->addComment($course, $author, 'This lesson is great.');
        $service->addReply($comment, $author, 'Thanks for the feedback.');

        $authorView = $service->queryFor($course, $author)->get();
        $viewerView = $service->queryFor($course, $viewer)->get();

        $this->assertCount(1, $authorView);
        $this->assertCount(1, $viewerView);
    }

    public function test_it_auto_approves_dashboard_commentables(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $course = $this->createCourse();
        $service = $this->app->make(CommentService::class);

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

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'title' => 'Session',
            'start_at' => now(),
            'duration_minutes' => 60,
        ]);

        $lessonComment = $service->addComment($lesson, $author, 'Lesson comment');
        $attachmentComment = $service->addComment($attachment, $author, 'Attachment comment');
        $sessionComment = $service->addComment($session, $author, 'Session comment');

        $this->assertTrue($lessonComment->approved);
        $this->assertTrue($attachmentComment->approved);
        $this->assertTrue($sessionComment->approved);

        $reply = $service->addReply($lessonComment, $author, 'Reply');
        $this->assertTrue($reply->approved);
    }

    public function test_it_supports_nested_replies_and_builds_reply_tree(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $student = User::factory()->create();
        $trainer = User::factory()->create();
        $course = $this->createCourse();

        $service = $this->app->make(CommentService::class);

        $comment = $service->addComment($course, $author, 'Main comment');
        $instructorReply = $service->addReply($comment, $trainer, 'Instructor reply');
        $studentReply = $service->addReply($comment, $student, 'Student follow up', $instructorReply);
        $nestedInstructorReply = $service->addReply($comment, $trainer, 'Instructor second reply', $studentReply);

        $commentWithReplies = $service->queryFor($course, $author)->firstOrFail();
        $tree = $service->buildReplyTree($commentWithReplies->allReplies);

        $this->assertCount(1, $tree);
        $this->assertSame($instructorReply->id, $tree->first()->id);
        $this->assertCount(1, $tree->first()->childReplies);
        $this->assertSame($studentReply->id, $tree->first()->childReplies->first()->id);
        $this->assertCount(1, $tree->first()->childReplies->first()->childReplies);
        $this->assertSame($nestedInstructorReply->id, $tree->first()->childReplies->first()->childReplies->first()->id);
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
