<?php

namespace Tests\Unit;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\OrderItemSeatStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\OrderApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_order_paid_and_creates_enrollment(): void
    {
        $user = User::factory()->create();

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => CourseStatus::Published,
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'status' => CourseGroupStatus::Open,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-1',
            'status' => OrderStatus::Pending,
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group A',
            'currency' => 'SAR',
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        $orderItem = OrderItem::query()->first();

        OrderItemSeat::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'purchaser_user_id' => $user->id,
            'invited_user_id' => $user->id,
            'status' => OrderItemSeatStatus::Invited,
        ]);

        app(OrderApprovalService::class)->approve($order);

        $order->refresh();

        $this->assertSame(OrderStatus::Paid->value, $order->status->value);
        $this->assertTrue(
            Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_group_id', $group->id)
                ->exists()
        );
    }

    public function test_it_creates_order_attribution_when_linked_to_service_request(): void
    {
        $user = User::factory()->create();
        $salesUser = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-2',
            'status' => OrderStatus::Pending,
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        ServiceRequest::query()->create([
            'source' => 'lead',
            'user_id' => $user->id,
            'name' => 'Lead User',
            'phone' => '0555555555',
            'status' => 'new',
            'assigned_to' => $salesUser->id,
            'priority' => 'normal',
            'converted_order_id' => $order->id,
        ]);

        app(OrderApprovalService::class)->approve($order);

        $this->assertTrue(OrderAttribution::query()->where('order_id', $order->id)->exists());
    }
}
