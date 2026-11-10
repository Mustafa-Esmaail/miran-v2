<?php

namespace Tests\Feature;

use App\Livewire\PaymentFailed;
use App\Livewire\PaymentSuccess;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentResultPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_success_page_renders_for_the_owning_user(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Jane Doe',
            'customer_phone' => '0512345678',
            'order_no' => 'ORD-RESULT-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => User::class,
            'purchasable_id' => $user->id,
            'title_snapshot' => 'Test Course',
            'currency' => 'SAR',
            'price_amount' => 500,
            'quantity' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(PaymentSuccess::class, ['order_no' => $order->order_no])
            ->assertSet('order_no', $order->order_no)
            ->assertSee($order->order_no)
            ->assertSee('Test Course')
            ->assertSee($order->customer_phone)
            ->assertSee($user->email);
    }

    public function test_payment_failed_page_renders_for_the_owning_user(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-RESULT-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
        ]);

        $this->actingAs($user);

        Livewire::test(PaymentFailed::class, ['order_no' => $order->order_no])
            ->assertSet('order_no', $order->order_no)
            ->assertSee($order->order_no);
    }

    public function test_payment_success_page_rejects_orders_belonging_to_another_user(): void
    {
        $owner = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'order_no' => 'ORD-RESULT-3',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
        ]);

        $intruder = User::factory()->create();
        $this->actingAs($intruder);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(PaymentSuccess::class, ['order_no' => $order->order_no]);
    }
}
