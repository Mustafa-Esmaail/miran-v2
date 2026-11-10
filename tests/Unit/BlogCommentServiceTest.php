<?php

namespace Tests\Unit;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use App\Services\BlogCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCommentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_moderates_comments(): void
    {
        $user = User::factory()->create();
        $category = BlogCategory::create([
            'title' => ['en' => 'News', 'ar' => 'أخبار'],
            'slug' => 'news',
            'status' => 'active',
        ]);
        $blog = Blog::create([
            'title' => ['en' => 'Update', 'ar' => 'تحديث'],
            'slug' => 'update',
            'content' => ['en' => 'Body', 'ar' => 'نص'],
            'author_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $service = $this->app->make(BlogCommentService::class);
        $comment = $service->addComment($blog, $user, 'Great post!');

        $this->assertSame('pending', $comment->status->value);

        $service->approve($comment);
        $this->assertSame('approved', $comment->fresh()->status->value);

        $service->reject($comment);
        $this->assertSame('rejected', $comment->fresh()->status->value);
    }
}
