<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardOrderInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(User $user, string $orderNo): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
    }

    public function test_order_invoice_relation_returns_the_latest_invoice(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'ORD-INV-1');

        $older = Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-OLD',
            'invoice_path' => 'invoices/old.pdf',
            'status' => 'draft',
            'amount' => 1000,
        ]);
        $latest = Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-NEW',
            'invoice_path' => 'invoices/new.pdf',
            'status' => 'submitted',
            'amount' => 1000,
        ]);

        $this->assertTrue($order->invoice->is($latest));
        $this->assertFalse($order->invoice->is($older));
    }

    public function test_dashboard_order_page_hides_invoice_link_when_no_invoice_exists(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'ORD-INV-2');
        $this->actingAs($user);

        $this->get(route('dashboard.orders.show', ['order_no' => $order->order_no]))
            ->assertOk()
            ->assertDontSee(__('web.orders.download_invoice'));
    }

    public function test_dashboard_order_page_hides_invoice_link_until_submitted(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'ORD-INV-3');
        Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-PENDING',
            'invoice_path' => 'invoices/pending.pdf',
            'status' => 'pending',
            'amount' => 1000,
        ]);
        $this->actingAs($user);

        $this->get(route('dashboard.orders.show', ['order_no' => $order->order_no]))
            ->assertOk()
            ->assertDontSee(__('web.orders.download_invoice'));
    }

    public function test_dashboard_order_page_shows_invoice_link_when_submitted(): void
    {
        config(['filesystems.disks.s3.bucket' => 'test-bucket']);
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(
            fn (string $path): string => 'https://cdn.example.test/'.$path
        );

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'ORD-INV-4');
        Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-SUBMITTED',
            'invoice_path' => 'invoices/submitted.pdf',
            'status' => 'submitted',
            'amount' => 1000,
        ]);
        $this->actingAs($user);

        $this->get(route('dashboard.orders.show', ['order_no' => $order->order_no]))
            ->assertOk()
            ->assertSee(__('web.orders.download_invoice'));
    }
}
