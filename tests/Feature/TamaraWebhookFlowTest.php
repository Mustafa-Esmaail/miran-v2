<?php

namespace Tests\Feature;

use App\DTOs\WebhookPayloadDto;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TamaraWebhookFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamara_approved_webhook_authorises_then_captures_then_marks_order_paid(): void
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
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $settings->shouldReceive('ordersSyncToSheetsEnabled')->andReturn(false);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-TAMARA-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 2400,
            'discount_amount' => 0,
            'total_amount' => 2400,
        ]);

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 2400,
            'quantity' => 1,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'tamara-checkout-1',
            'status' => PaymentStatus::Pending,
            'amount' => 2400,
            'payload' => [
                'tamara_response' => [
                    'order_id' => 'tamara-order-123',
                    'checkout_id' => 'tamara-checkout-1',
                ],
            ],
        ]);

        $service = $this->app->make(PaymentService::class);
        $service->handleWebhook(new WebhookPayloadDto(
            'tamara',
            'tamara-checkout-1',
            'approved',
            [
                'order_id' => 'tamara-order-123',
                'checkout_id' => 'tamara-checkout-1',
                'status' => 'approved',
            ]
        ));

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('paid', $order->status->value);
        $this->assertSame('authorised', data_get($payment->payload, 'tamara_authorise_response.status'));
        $this->assertSame('fully_captured', data_get($payment->payload, 'tamara_capture_response.status'));

        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/orders/tamara-order-123/authorise');
        });
        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/payments/capture')
                && data_get($request->data(), 'order_id') === 'tamara-order-123';
        });
    }

    public function test_tamara_approved_webhook_skips_redundant_capture_when_authorise_already_fully_captured(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/orders/*/authorise' => Http::response([
                'order_id' => 'tamara-order-123',
                'status' => 'fully_captured',
                'auto_captured' => true,
            ], 200),
            'https://api-sandbox.tamara.co/payments/capture' => Http::response([], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $settings->shouldReceive('ordersSyncToSheetsEnabled')->andReturn(false);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-TAMARA-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 2400,
            'discount_amount' => 0,
            'total_amount' => 2400,
        ]);

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 2400,
            'quantity' => 1,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'tamara-checkout-2',
            'status' => PaymentStatus::Pending,
            'amount' => 2400,
            'payload' => [
                'tamara_response' => [
                    'order_id' => 'tamara-order-123',
                    'checkout_id' => 'tamara-checkout-2',
                ],
            ],
        ]);

        $service = $this->app->make(PaymentService::class);
        $service->handleWebhook(new WebhookPayloadDto(
            'tamara',
            'tamara-checkout-2',
            'approved',
            [
                'order_id' => 'tamara-order-123',
                'checkout_id' => 'tamara-checkout-2',
                'status' => 'approved',
            ]
        ));

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('paid', $order->status->value);
        $this->assertSame('fully_captured', data_get($payment->payload, 'tamara_authorise_response.status'));
        $this->assertNull(data_get($payment->payload, 'tamara_capture_response'));

        Http::assertSentCount(1);
        Http::assertNotSent(function ($request): bool {
            return str_ends_with($request->url(), '/payments/capture');
        });
    }

    public function test_tamara_capture_already_captured_conflict_still_marks_order_paid(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/orders/*/authorise' => Http::response([
                'order_id' => 'tamara-order-123',
                'status' => 'authorised',
            ], 200),
            'https://api-sandbox.tamara.co/payments/capture' => Http::response([
                'message' => 'The order tamara-order-123 cannot be moved to the status fully_captured from fully_captured',
                'errors' => [[
                    'error_code' => 'transition_not_allowed',
                    'data' => [
                        'order_id' => 'tamara-order-123',
                        'old_state' => 'fully_captured',
                        'new_state' => 'fully_captured',
                    ],
                ]],
            ], 409),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->once()->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $settings->shouldReceive('ordersSyncToSheetsEnabled')->andReturn(false);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-TAMARA-3',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 2400,
            'discount_amount' => 0,
            'total_amount' => 2400,
        ]);

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Course Group',
            'currency' => 'SAR',
            'price_amount' => 2400,
            'quantity' => 1,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'tamara-checkout-3',
            'status' => PaymentStatus::Pending,
            'amount' => 2400,
            'payload' => [
                'tamara_response' => [
                    'order_id' => 'tamara-order-123',
                    'checkout_id' => 'tamara-checkout-3',
                ],
            ],
        ]);

        $service = $this->app->make(PaymentService::class);
        $service->handleWebhook(new WebhookPayloadDto(
            'tamara',
            'tamara-checkout-3',
            'approved',
            [
                'order_id' => 'tamara-order-123',
                'checkout_id' => 'tamara-checkout-3',
                'status' => 'approved',
            ]
        ));

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('paid', $order->status->value);
    }
}
