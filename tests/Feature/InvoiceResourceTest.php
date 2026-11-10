<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(string $invoiceNumber): Invoice
    {
        $order = Order::factory()->create();

        return Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => $invoiceNumber,
            'invoice_path' => 'invoices/sample.pdf',
            'status' => 'draft',
            'amount' => 10000,
        ]);
    }

    public function test_invoice_resource_requires_permission_to_view_any(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin');

        $this->assertFalse(InvoiceResource::canViewAny());

        Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'invoices.viewAny', 'guard_name' => 'admin']);
        $user->assignRole('admin');
        $user->givePermissionTo('invoices.viewAny');

        $this->assertTrue(InvoiceResource::canViewAny());
    }

    public function test_list_page_renders_and_shows_invoice_number(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin');

        Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'invoices.viewAny', 'guard_name' => 'admin']);
        $user->assignRole('admin');
        $user->givePermissionTo('invoices.viewAny');

        $invoice = $this->makeInvoice('INV-000123');

        Livewire::test(ListInvoices::class)
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }
}
