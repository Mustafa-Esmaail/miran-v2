<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Course;
use App\Services\CommentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommentModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_and_hides_comments_and_replies(): void
    {
        $course = Course::query()->first();
        if (! $course) {
            $category = \App\Models\Category::query()->create([
                'name' => ['en' => 'Category'],
                'slug' => Str::slug(Str::random(12)),
                'is_active' => true,
            ]);
            $course = Course::query()->create([
                'category_id' => $category->id,
                'title' => ['en' => 'Course'],
                'slug' => Str::slug(Str::random(12)),
            ]);
        }

        $comment = Comment::factory()->create([
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
            'approved' => false,
            'hidden' => false,
        ]);
        $reply = CommentReply::factory()->create([
            'comment_id' => $comment->id,
            'approved' => false,
            'hidden' => false,
        ]);

        $service = $this->app->make(CommentModerationService::class);

        $service->approve($comment);
        $service->hide($comment);

        $service->approveReply($reply);
        $service->hideReply($reply);

        $this->assertTrue($comment->fresh()->approved);
        $this->assertTrue($comment->fresh()->hidden);
        $this->assertTrue($reply->fresh()->approved);
        $this->assertTrue($reply->fresh()->hidden);
    }
}
