<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PaymentStartConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_payment_uses_settings_provider_config_for_redirect_url(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::response([
                'checkout_id' => 'tamara-checkout-1',
                'checkout_url' => 'https://checkout-sandbox.tamara.co/pay/abc',
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'init_api_key' => 'tamara-init-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create([
            'phone' => '0551234567',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-PAY-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 15000,
            'discount_amount' => 0,
            'total_amount' => 15000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 15000,
            'quantity' => 1,
        ]);

        $result = app(PaymentService::class)->startPayment($order, 'tamara');

        $this->assertSame('https://checkout-sandbox.tamara.co/pay/abc', $result['redirect_url']);
        $this->assertDatabaseHas((new Payment)->getTable(), [
            'order_id' => $order->id,
            'provider' => 'tamara',
        ]);
        Http::assertSent(function ($request) use ($order): bool {
            $data = $request->data();

            return ($data['items'][0]['type'] ?? null) === 'Digital'
                && ($data['consumer']['phone_number'] ?? null) === '551234567'
                && ($data['merchant_url']['notification'] ?? null) === url('/api/webhooks/tamara')
                && str_contains((string) ($data['merchant_url']['success'] ?? null), route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]))
                && is_array($data['risk_assessment'] ?? null)
                && is_array($data['additional_data'] ?? null);
        });
        Http::assertSentCount(1);
    }

    public function test_start_payment_uses_tamara_init_api_key_for_checkout_request(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::response([
                'checkout_id' => 'tamara-checkout-init',
                'checkout_url' => 'https://checkout-sandbox.tamara.co/pay/init',
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => '',
            'init_api_key' => 'tamara-init-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-PAY-INIT',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 15000,
            'discount_amount' => 0,
            'total_amount' => 15000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 15000,
            'quantity' => 1,
        ]);

        $result = app(PaymentService::class)->startPayment($order, 'tamara');

        $this->assertSame('https://checkout-sandbox.tamara.co/pay/init', $result['redirect_url']);
    }

    public function test_start_payment_logs_tamara_failure_response_details(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::response([
                'message' => 'invalid credentials',
            ], 401),
        ]);
        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'bad-key',
            'init_api_key' => 'bad-init-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-PAY-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 15000,
            'discount_amount' => 0,
            'total_amount' => 15000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 15000,
            'quantity' => 1,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tamara checkout initialization failed.');

        app(PaymentService::class)->startPayment($order, 'tamara');
    }

    public function test_start_payment_retries_tamara_without_forced_payment_type_when_unsupported(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::sequence()
                ->push([
                    'message' => 'Your order information is invalid',
                    'errors' => [
                        ['error_code' => 'payment_type_unsupported'],
                    ],
                ], 400)
                ->push([
                    'checkout_id' => 'tamara-checkout-2',
                    'checkout_url' => 'https://checkout-sandbox.tamara.co/pay/def',
                ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'init_api_key' => 'tamara-init-key',
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'instalments' => 3,
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-PAY-3',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 15000,
            'discount_amount' => 0,
            'total_amount' => 15000,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 15000,
            'quantity' => 1,
        ]);

        $result = app(PaymentService::class)->startPayment($order, 'tamara');

        $this->assertSame('https://checkout-sandbox.tamara.co/pay/def', $result['redirect_url']);
        Http::assertSentCount(2);

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return ($data['payment_type'] ?? null) === 'PAY_BY_INSTALMENTS'
                && array_key_exists('instalments', $data);
        });

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return ! array_key_exists('payment_type', $data)
                && ! array_key_exists('instalments', $data);
        });
    }
}
