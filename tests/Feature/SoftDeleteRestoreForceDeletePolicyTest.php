<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SoftDeleteRestoreForceDeletePolicyTest extends TestCase
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

    public function test_regular_user_cannot_restore_or_force_delete_a_trashed_order(): void
    {
        $order = $this->makeOrder('ORD-POLICY-1');
        $order->delete();

        $regularUser = User::factory()->create();

        $this->assertFalse($regularUser->can('restore', $order));
        $this->assertFalse($regularUser->can('forceDelete', $order));
    }

    public function test_admin_role_can_restore_and_force_delete_a_trashed_order(): void
    {
        $order = $this->makeOrder('ORD-POLICY-2');
        $order->delete();

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'orders.delete', 'guard_name' => 'admin']));
        $this->actingAs($admin, 'admin');

        $this->assertTrue($admin->can('restore', $order));
        $this->assertTrue($admin->can('forceDelete', $order));
    }

    public function test_super_admin_role_can_restore_and_force_delete_a_trashed_service_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();
        $serviceRequest->delete();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']));
        $superAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'service_requests.delete', 'guard_name' => 'admin']));
        $this->actingAs($superAdmin, 'admin');

        $this->assertTrue($superAdmin->can('restore', $serviceRequest));
        $this->assertTrue($superAdmin->can('forceDelete', $serviceRequest));

        $regularUser = User::factory()->create();
        $this->assertFalse($regularUser->can('restore', $serviceRequest));
    }

    public function test_regular_user_cannot_restore_a_trashed_coupon_or_payment(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'POLICY10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);
        $coupon->delete();

        $order = $this->makeOrder('ORD-POLICY-3');
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'ref-policy-1',
            'status' => 'pending',
            'amount' => 1000,
        ]);
        $payment->delete();

        $regularUser = User::factory()->create();

        $this->assertFalse($regularUser->can('restore', $coupon));
        $this->assertFalse($regularUser->can('restore', $payment));
        $this->assertFalse($regularUser->can('forceDelete', $coupon));
        $this->assertFalse($regularUser->can('forceDelete', $payment));
    }
}
