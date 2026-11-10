<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_prevents_duplicate_purchase(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-DUP',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => $course->getTranslation('title', app()->getLocale()) ?? 'Course',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $group->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $service = $this->app->make(CheckoutService::class);

        $this->expectException(\RuntimeException::class);
        $service->createOrderFromCart($user->id);
    }

    public function test_checkout_prevents_duplicate_purchase_across_course_groups(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-multi-group', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-multi-group',
            'status' => 'published',
        ]);
        $enrolledGroup = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group A'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $otherGroup = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group B'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-DUP-MULTI',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $enrolledGroup->id,
            'title_snapshot' => $course->getTranslation('title', app()->getLocale()) ?? 'Course',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $enrolledGroup->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $enrolledGroup->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $otherGroup->id,
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $service = $this->app->make(CheckoutService::class);

        $this->expectException(\RuntimeException::class);
        $service->createOrderFromCart($user->id);
    }

    public function test_checkout_enforces_capacity_and_deadline(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat2', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course2',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group'],
            'group_price_amount' => 10000,
            'capacity' => 1,
            'enrollment_deadline_at' => now()->subDay(),
            'status' => 'open',
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $service = $this->app->make(CheckoutService::class);

        $this->expectException(\RuntimeException::class);
        $service->createOrderFromCart($user->id);
    }
}
