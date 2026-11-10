<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Services\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WishlistServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_wishlist_for_course(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $service = $this->app->make(WishlistService::class);

        $added = $service->toggle($user->id, Course::class, $course->id);
        $this->assertTrue($added);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'wishable_type' => Course::class,
            'wishable_id' => $course->id,
        ]);
        if (Schema::hasColumn('wishlist_items', 'course_id')) {
            $this->assertDatabaseHas('wishlist_items', [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ]);
        }

        $removed = $service->toggle($user->id, Course::class, $course->id);
        $this->assertFalse($removed);
        $this->assertDatabaseMissing('wishlist_items', [
            'user_id' => $user->id,
            'wishable_type' => Course::class,
            'wishable_id' => $course->id,
        ]);
    }

    public function test_toggle_wishlist_for_bundle(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-bundle', 'is_active' => true]);
        $bundle = Bundle::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Bundle'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'bundle',
            'price' => 250,
            'is_active' => true,
        ]);

        $service = $this->app->make(WishlistService::class);

        $added = $service->toggle($user->id, Bundle::class, $bundle->id);
        $this->assertTrue($added);
        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'wishable_type' => Bundle::class,
            'wishable_id' => $bundle->id,
        ]);
        if (Schema::hasColumn('wishlist_items', 'bundle_id')) {
            $this->assertDatabaseHas('wishlist_items', [
                'user_id' => $user->id,
                'bundle_id' => $bundle->id,
            ]);
        }

        $removed = $service->toggle($user->id, Bundle::class, $bundle->id);
        $this->assertFalse($removed);
        $this->assertDatabaseMissing('wishlist_items', [
            'user_id' => $user->id,
            'wishable_type' => Bundle::class,
            'wishable_id' => $bundle->id,
        ]);
    }
}
