<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class DashboardOrderTranslationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('translationKeyProvider')]
    public function test_dashboard_translation_keys_exist(string $locale, string $key): void
    {
        app()->setLocale($locale);

        $this->assertTrue(Lang::has($key));
        $this->assertNotSame($key, trans($key));
    }

    /**
     * @return array<int, array{0:string, 1:string}>
     */
    public static function translationKeyProvider(): array
    {
        $keys = [
            'web.orders.status_values.pending',
            'web.orders.status_values.paid',
            'web.orders.status_values.rejected',
            'web.orders.status_values.refunded',
            'web.orders.status_values.cancelled',
            'web.orders.payment_status_values.pending',
            'web.orders.payment_status_values.paid',
            'web.orders.payment_status_values.failed',
            'web.orders.payment_status_values.unpaid',
            'web.orders.payment_method_values.bank_transfer',
            'web.orders.payment_method_values.cash',
            'web.orders.payment_method_values.unpaid',
            'web.orders.item_type.course_group',
            'web.orders.item_type.bundle',
            'web.orders.notes.bank_transfer_order',
            'web.checkout.seat_status_values.unassigned',
            'web.checkout.seat_status_values.invited',
            'web.checkout.seat_status_values.enrolled',
            'web.checkout.seat_status_values.canceled',
            'web.common.currency.sar',
        ];

        return array_merge(
            array_map(fn (string $key) => ['en', $key], $keys),
            array_map(fn (string $key) => ['ar', $key], $keys)
        );
    }
}
