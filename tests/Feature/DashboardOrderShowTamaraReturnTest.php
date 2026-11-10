<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardOrderShowTamaraReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamara_success_return_approved_marks_order_paid(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/orders/*/authorise' => Http::response([
                'order_id' => 'tamara-order-123',
                'status' => 'authorised',
            ], 200),
            'https://api-sandbox.tamara.co/payments/capture' => Http::response([
                'order_id' => 'tamara-order-123',
                'status' => 'fully_captured',
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldIgnoreMissing();
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
        $this->actingAs($user);

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-RETURN-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 2100,
            'discount_amount' => 0,
            'total_amount' => 2100,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 2100,
            'quantity' => 1,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'tamara-checkout-1',
            'status' => PaymentStatus::Pending,
            'amount' => 2100,
            'payload' => [
                'tamara_response' => [
                    'order_id' => 'tamara-order-123',
                    'checkout_id' => 'tamara-checkout-1',
                ],
            ],
        ]);

        $this->get(route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]).'?payment=success&paymentStatus=approved&orderId=tamara-order-123')
            ->assertRedirect(route('payment-success', ['order_no' => $order->order_no]));

        $order->refresh();
        $payment = $order->payments()->latest('id')->firstOrFail();

        $this->assertSame('paid', $order->status->value);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('authorised', data_get($payment->payload, 'tamara_authorise_response.status'));
        $this->assertSame('fully_captured', data_get($payment->payload, 'tamara_capture_response.status'));
    }

    public function test_tamara_failure_return_redirects_without_processing_payment(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-RETURN-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 2100,
            'discount_amount' => 0,
            'total_amount' => 2100,
        ]);

        $this->get(route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]).'?payment=failure')
            ->assertRedirect(route('payment-failed', ['order_no' => $order->order_no]));

        $order->refresh();

        $this->assertSame('pending', $order->status->value);
        Http::assertNothingSent();
    }
}
