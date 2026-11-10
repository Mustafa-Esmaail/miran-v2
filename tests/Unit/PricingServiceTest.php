<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponTarget;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_validation_and_calculation(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Course'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $coupon = Coupon::create([
            'title' => ['ar' => 'Description'],
            'code' => 'SAVE10',
            'type' => 'percent',
            'value' => 10,
            'min_order_amount' => 1000,
            'is_active' => true,
        ]);

        CouponTarget::create([
            'coupon_id' => $coupon->id,
            'target_type' => 'course_group',
            'target_id' => $group->id,
        ]);

        $cart = Cart::create(['user_id' => $user->id, 'coupon_id' => $coupon->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $service = $this->app->make(PricingService::class);
        $cart->load('items.purchasable', 'coupon.targets');

        $totals = $service->calculateTotals($cart);

        $this->assertSame(10000, $totals->subtotalAmount);
        $this->assertSame(1000, $totals->discountAmount);
        $this->assertSame(9000, $totals->totalAmount);
    }

    public function test_coupon_respects_quantity_in_subtotal(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-qty', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-qty',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Course'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $coupon = Coupon::create([
            'title' => ['ar' => 'Description'],
            'code' => 'SAVE20',
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 1000,
            'is_active' => true,
        ]);

        CouponTarget::create([
            'coupon_id' => $coupon->id,
            'target_type' => 'course_group',
            'target_id' => $group->id,
        ]);

        $cart = Cart::create(['user_id' => $user->id, 'coupon_id' => $coupon->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => 3,
        ]);

        $service = $this->app->make(PricingService::class);
        $cart->load('items.purchasable', 'coupon.targets');

        $totals = $service->calculateTotals($cart);

        $this->assertSame(30000, $totals->subtotalAmount);
        $this->assertSame(6000, $totals->discountAmount);
        $this->assertSame(24000, $totals->totalAmount);
    }

    public function test_coupon_rejects_cart_below_min_items_count(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-min-items', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-min-items',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Course'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $coupon = Coupon::create([
            'title' => ['ar' => 'Description'],
            'code' => 'SAVE30',
            'type' => 'percent',
            'value' => 10,
            'min_items_count' => 2,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id, 'coupon_id' => $coupon->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $service = $this->app->make(PricingService::class);
        $cart->load('items.purchasable', 'coupon.targets');

        $this->assertSame(
            'min_items_count',
            $service->couponRejectionReason($coupon, $user->id, 10000, $cart)
        );

        $cart->items()->first()->update(['quantity' => 2]);
        $cart->load('items.purchasable', 'coupon.targets');

        $this->assertNull(
            $service->couponRejectionReason($coupon, $user->id, 20000, $cart)
        );
    }

    public function test_order_coupon_rejection_also_enforces_min_items_count(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-order-min-items', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-order-min-items',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Course'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $coupon = Coupon::create([
            'title' => ['ar' => 'Description'],
            'code' => 'SAVE40',
            'type' => 'percent',
            'value' => 10,
            'min_items_count' => 2,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-MIN-ITEMS',
            'status' => 'pending',
            'currency' => 'SAR',
            'coupon_id' => $coupon->id,
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);

        $service = $this->app->make(PricingService::class);
        $order->load('items.purchasable', 'coupon.targets');

        $this->assertSame(
            'min_items_count',
            $service->orderCouponRejectionReason($coupon, $user->id, 10000, $order)
        );

        $order->items->first()->update(['quantity' => 2]);
        $order->load('items.purchasable', 'coupon.targets');

        $this->assertNull(
            $service->orderCouponRejectionReason($coupon, $user->id, 20000, $order)
        );
    }
}
