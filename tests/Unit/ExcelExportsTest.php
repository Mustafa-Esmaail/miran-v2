<?php

namespace Tests\Unit;

use App\Exports\EnrollmentsExport;
use App\Exports\OrdersExport;
use App\Exports\PaymentsExport;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class ExcelExportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
    }

    public function test_orders_export_maps_rows(): void
    {
        $export = new OrdersExport;
        $createdAt = Carbon::parse('2026-02-04 12:00:00');

        $order = new Order([
            'order_no' => 'ORDER-1001',
            'status' => 'paid',
            'subtotal_amount' => 1000,
            'discount_amount' => 100,
            'total_amount' => 900,
            'currency' => 'SAR',
            'notes' => 'Test order',
        ]);
        $order->setAttribute('created_at', $createdAt);

        $order->setRelation('user', new User([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]));

        $order->setRelation('coupon', new Coupon([
            'code' => 'SAVE10',
        ]));

        $this->assertSame([
            'Order No',
            'Customer',
            'Phone',
            'Email',
            'Status',
            'Subtotal',
            'Discount',
            'Total',
            'Currency',
            'Coupon',
            'Notes',
            'Created At',
        ], $export->headings());

        $this->assertSame([
            'ORDER-1001',
            'Ada Lovelace',
            '',
            'ada@example.com',
            'paid',
            1000,
            100,
            900,
            'SAR',
            'SAVE10',
            'Test order',
            '2026-02-04 12:00:00',
        ], $export->map($order));
    }

    public function test_payments_export_maps_rows(): void
    {
        $export = new PaymentsExport;
        $createdAt = Carbon::parse('2026-02-04 13:00:00');

        $order = new Order([
            'order_no' => 'ORDER-2001',
        ]);

        $payment = new Payment([
            'provider' => 'stripe',
            'provider_reference' => 'REF-123',
            'status' => 'paid',
            'amount' => 5000,
        ]);
        $payment->setAttribute('created_at', $createdAt);

        $payment->setRelation('order', $order);

        $this->assertSame([
            'Order No',
            'Provider',
            'Reference',
            'Status',
            'Amount',
            'Created At',
        ], $export->headings());

        $this->assertSame([
            'ORDER-2001',
            'stripe',
            'REF-123',
            'paid',
            5000,
            '2026-02-04 13:00:00',
        ], $export->map($payment));
    }

    public function test_enrollments_export_maps_rows(): void
    {
        $export = new EnrollmentsExport;
        $createdAt = Carbon::parse('2026-02-04 14:00:00');
        $activatedAt = Carbon::parse('2026-02-04 14:30:00');

        $enrollment = new Enrollment([
            'status' => 'active',
            'is_invited' => true,
        ]);
        $enrollment->setAttribute('created_at', $createdAt);
        $enrollment->setAttribute('activated_at', $activatedAt);

        $enrollment->setRelation('user', new User([
            'name' => 'Alan Turing',
            'email' => 'alan@example.com',
        ]));

        $enrollment->setRelation('course', new Course([
            'title' => 'Algorithms',
        ]));

        $enrollment->setRelation('courseGroup', new CourseGroup([
            'title' => 'CS Bundle',
        ]));

        $this->assertSame([
            'User',
            'Email',
            'Course',
            'Course Group',
            'Status',
            'Invited',
            'Activated At',
            'Created At',
        ], $export->headings());

        $this->assertSame([
            'Alan Turing',
            'alan@example.com',
            'Algorithms',
            'CS Bundle',
            'active',
            __('admin.labels.yes'),
            '2026-02-04 14:30:00',
            '2026-02-04 14:00:00',
        ], $export->map($enrollment));
    }
}
