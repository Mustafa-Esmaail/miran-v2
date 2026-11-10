<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoogleSheetsOrderRowCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_coupon_discount_is_split_evenly_across_order_items(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Fixed 150'],
            'code' => 'FIXED150',
            'type' => CouponType::Fixed,
            'value' => 150,
        ]);

        $order = $this->createOrder(discountAmount: 150, couponId: $coupon->id);
        $this->addItem($order, price: 1000);
        $this->addItem($order, price: 1000);
        $this->addItem($order, price: 1000);

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order->fresh());

        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame('950', $row[9]); // column J (0-indexed) = 1000 - (150/3)
        }
    }

    public function test_percent_coupon_discount_is_applied_per_item_price(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Percent 10'],
            'code' => 'PERCENT10',
            'type' => CouponType::Percent,
            'value' => 10,
        ]);

        $order = $this->createOrder(discountAmount: 200, couponId: $coupon->id);
        $this->addItem($order, price: 1000);
        $this->addItem($order, price: 1000);

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order->fresh());

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertSame('900', $row[9]); // 1000 - 10% of 1000
        }
    }

    public function test_order_without_a_coupon_keeps_the_raw_item_price(): void
    {
        $order = $this->createOrder(discountAmount: 0, couponId: null);
        $this->addItem($order, price: 1000);

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order->fresh());

        $this->assertSame('1000', $rows[0][9]);
    }

    private function createOrder(int $discountAmount, ?int $couponId): Order
    {
        $user = User::factory()->create();

        return Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 0,
            'discount_amount' => $discountAmount,
            'total_amount' => 0,
            'coupon_id' => $couponId,
        ]);
    }

    private function addItem(Order $order, int $price): OrderItem
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category '.Str::uuid()],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course group',
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'price_amount' => $price,
            'quantity' => 1,
        ]);
    }
}
