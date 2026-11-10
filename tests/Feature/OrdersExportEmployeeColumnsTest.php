<?php

namespace Tests\Feature;

use App\Exports\OrdersExport;
use App\Models\Employee;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersExportEmployeeColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_headings_include_employee_columns(): void
    {
        $headings = (new OrdersExport)->headings();

        $this->assertContains('Employee Code', $headings);
        $this->assertContains('Employee Name', $headings);
        $this->assertContains('Payment Method', $headings);
    }

    public function test_map_includes_referred_employee_code_and_name(): void
    {
        $employee = Employee::query()->create([
            'employee_code' => 'EMP-42',
            'name' => 'Sara Ahmed',
        ]);

        $user = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'referred_by_employee_id' => $employee->id,
        ]);

        $row = (new OrdersExport)->map($order->fresh(['referredByEmployee']));

        $this->assertSame('EMP-42', $row[11]);
        $this->assertSame('Sara Ahmed', $row[12]);
    }

    public function test_map_handles_orders_without_a_referred_employee(): void
    {
        $user = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        $row = (new OrdersExport)->map($order->fresh(['referredByEmployee']));

        $this->assertSame('', $row[11]);
        $this->assertSame('', $row[12]);
    }
}
