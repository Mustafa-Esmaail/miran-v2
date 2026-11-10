<?php

namespace Tests\Unit;

use App\Services\CheckoutService;
use App\Services\Settings\SettingsService;
use Mockery;
use Tests\TestCase;

class CheckoutPaymentMethodsTest extends TestCase
{
    public function test_checkout_methods_include_only_configured_installment_providers(): void
    {
        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentsEnabled')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->once()->andReturn(true);
        $settings->shouldReceive('paymentProviderIsReady')->with('tabby')->once()->andReturn(false);
        $settings->shouldReceive('getValue')->with('payments.enable_bank_transfer', true)->once()->andReturn(true);

        $service = app(CheckoutService::class);
        $methods = $service->getAvailablePaymentMethods($settings);

        $this->assertArrayHasKey('tamara', $methods);
        $this->assertArrayNotHasKey('tabby', $methods);
        $this->assertArrayHasKey('cash', $methods);
        $this->assertArrayHasKey('bank_transfer', $methods);
    }

    public function test_free_orders_only_show_the_free_checkout_option(): void
    {
        $settings = Mockery::mock(SettingsService::class);

        $service = app(CheckoutService::class);
        $methods = $service->getAvailablePaymentMethods($settings, true);

        $this->assertSame([
            'free' => __('web.checkout.free'),
        ], $methods);
    }
}
