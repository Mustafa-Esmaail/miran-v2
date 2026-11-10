<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Livewire\DashboardOrderRedirecting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class CheckoutRedirectingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamara_checkout_persists_redirect_url_and_buffer_page_reads_it(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::response([
                'checkout_id' => 'tamara-checkout-id',
                'checkout_url' => 'https://checkout.tamara.co/pay/tamara-checkout-id',
                'order_id' => 'tamara-order-id',
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->andReturn(true);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-BUFFER-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1350,
            'discount_amount' => 0,
            'total_amount' => 1350,
        ]);

        $result = app(PaymentService::class)->startPayment($order, 'tamara');

        $this->assertSame('https://checkout.tamara.co/pay/tamara-checkout-id', $result['redirect_url']);

        $latestPayment = $order->payments()->latest()->first();
        $this->assertSame('https://checkout.tamara.co/pay/tamara-checkout-id', $latestPayment->payload['redirect_url']);

        // The buffer page reads that same stored URL rather than trusting any client input.
        $this->actingAs($user);
        Livewire::test(DashboardOrderRedirecting::class, ['order_no' => $order->order_no])
            ->assertSet('redirectUrl', 'https://checkout.tamara.co/pay/tamara-checkout-id');
    }

    public function test_buffer_page_falls_back_to_orders_show_when_no_redirect_url_stored(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-BUFFER-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
        ]);

        $this->actingAs($user);
        Livewire::test(DashboardOrderRedirecting::class, ['order_no' => $order->order_no])
            ->assertRedirect(route('dashboard.orders.show', ['order_no' => $order->order_no]));
    }

    public function test_buffer_page_redirects_to_success_instead_of_re_offering_payment_when_already_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-BUFFER-4',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1350,
            'discount_amount' => 0,
            'total_amount' => 1350,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'tamara-checkout-paid',
            'status' => PaymentStatus::Paid,
            'amount' => 1350,
            'payload' => ['redirect_url' => 'https://checkout.tamara.co/pay/already-paid'],
        ]);

        $this->actingAs($user);
        Livewire::test(DashboardOrderRedirecting::class, ['order_no' => $order->order_no])
            ->assertRedirect(route('payment-success', ['order_no' => $order->order_no]));
    }

    public function test_buffer_page_rejects_orders_belonging_to_another_user(): void
    {
        $owner = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'order_no' => 'ORD-BUFFER-3',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
        ]);

        $intruder = User::factory()->create();
        $this->actingAs($intruder);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(DashboardOrderRedirecting::class, ['order_no' => $order->order_no]);
    }
}
