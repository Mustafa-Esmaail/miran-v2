<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CartLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_actions_write_lms_logs(): void
    {
        Log::shouldReceive('channel')->with('lms')->andReturnSelf()->atLeast()->once();

        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => 'Business'],
            'slug' => 'business',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Intro'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'intro-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 100,
            'status' => 'open',
        ]);

        Log::shouldReceive('info')->withArgs(function ($message, $context) use ($user, $group) {
            return $message === 'cart.add_item'
                && $context['user_id'] === $user->id
                && $context['purchasable_type'] === \App\Models\CourseGroup::class
                && $context['purchasable_id'] === $group->id;
        })->once();

        $service = app(CartService::class);
        $item = $service->addItem($user->id, \App\Models\CourseGroup::class, $group->id);

        Log::shouldReceive('info')->withArgs(function ($message, $context) use ($user, $item) {
            return $message === 'cart.remove_item'
                && $context['user_id'] === $user->id
                && $context['cart_item_id'] === $item->id;
        })->once();

        $service->removeItem($user->id, $item->id);
    }
}
