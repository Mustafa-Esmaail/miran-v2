<?php

namespace Tests\Unit;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use App\Services\BlogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_unique_slugs_and_publishes(): void
    {
        $user = User::factory()->create();
        $category = BlogCategory::create([
            'title' => ['en' => 'Announcements', 'ar' => 'إعلانات'],
            'slug' => 'announcements',
            'status' => 'active',
        ]);

        $service = $this->app->make(BlogService::class);

        $first = $service->createPost([
            'title' => ['en' => 'New Release', 'ar' => 'إصدار جديد'],
            'content' => ['en' => 'Content', 'ar' => 'محتوى'],
            'category_id' => $category->id,
        ], $user);

        $second = $service->createPost([
            'title' => ['en' => 'New Release', 'ar' => 'إصدار جديد'],
            'content' => ['en' => 'Content', 'ar' => 'محتوى'],
            'category_id' => $category->id,
        ], $user);

        $this->assertNotSame($first->slug, $second->slug);

        $service->publish($first);
        $this->assertNotNull($first->fresh()->published_at);

        $service->unpublish($first);
        $this->assertNull($first->fresh()->published_at);

        $existing = Blog::query()->where('slug', $second->slug)->first();
        $this->assertNotNull($existing);
    }
}
