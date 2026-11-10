<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletedByTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $orderNo): Order
    {
        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => $orderNo,
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
    }

    public function test_soft_deleting_an_order_records_who_deleted_it(): void
    {
        $order = $this->makeOrder('ORD-DELETEDBY-1');
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $order->delete();

        $this->assertSame($admin->id, $order->fresh()->deleted_by);
    }

    public function test_restoring_an_order_clears_the_deleted_by_marker(): void
    {
        $order = $this->makeOrder('ORD-DELETEDBY-2');
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $order->delete();
        $this->assertNotNull($order->fresh()->deleted_by);

        $order->fresh()->restore();

        $this->assertNull($order->fresh()->deleted_by);
    }

    public function test_soft_deleting_a_service_request_records_who_deleted_it(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $serviceRequest->delete();

        $this->assertSame($admin->id, $serviceRequest->fresh()->deleted_by);
    }

    public function test_soft_deleting_a_coupon_records_who_deleted_it(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'DELETEDBY10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $coupon->delete();

        $this->assertSame($admin->id, $coupon->fresh()->deleted_by);
    }
}
