<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\OrderNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderNoticeActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithOrderPermissions(User $user, bool $withNoticePermission): void
    {
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->firstOrCreate(['name' => "orders.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['orders.viewAny', 'orders.view']);

        if ($withNoticePermission) {
            Permission::query()->firstOrCreate(['name' => 'orders.notice.create', 'guard_name' => 'admin']);
            $user->givePermissionTo('orders.notice.create');
        }
    }

    private function makeOrder(string $orderNo): Order
    {
        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
    }

    public function test_add_notice_action_creates_an_order_notice_with_the_acting_user(): void
    {
        $user = User::factory()->create();
        $this->actingAsWithOrderPermissions($user, withNoticePermission: true);

        $order = $this->makeOrder('ORD-NOTICE-1');

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->callTableAction('add_notice', $order, data: ['note' => 'Called the customer to confirm details.']);

        $notice = OrderNotice::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('Called the customer to confirm details.', $notice->note);
        $this->assertSame($user->id, $notice->user_id);

        // Original `notes` column on the order must remain untouched.
        $order->refresh();
        $this->assertNull($order->notes);
    }

    public function test_add_notice_action_is_hidden_without_the_notice_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAsWithOrderPermissions($user, withNoticePermission: false);

        $order = $this->makeOrder('ORD-NOTICE-2');

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertTableActionHidden('add_notice', $order);
    }
}
