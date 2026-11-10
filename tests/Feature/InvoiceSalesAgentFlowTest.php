<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceSalesAgentFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function grantInvoicePermissions(User $user, string $roleName, array $permissions): void
    {
        Config::set('auth.defaults.guard', 'admin');

        $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        $user->assignRole($role);
        $user->givePermissionTo($permissions);
    }

    public function test_sales_agent_only_sees_their_own_invoices_while_accountant_sees_all(): void
    {
        $salesAgentUser = User::factory()->create();
        $otherSalesAgentUser = User::factory()->create();
        $accountantUser = User::factory()->create();

        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        Employee::factory()->create(['user_id' => $otherSalesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);

        $ownInvoice = Invoice::factory()->create(['created_by' => $salesAgentUser->id]);
        $othersInvoice = Invoice::factory()->create(['created_by' => $otherSalesAgentUser->id]);

        $this->actingAs($salesAgentUser, 'admin');
        $visibleToSalesAgent = InvoiceResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($visibleToSalesAgent->contains($ownInvoice->id));
        $this->assertFalse($visibleToSalesAgent->contains($othersInvoice->id));

        $this->actingAs($accountantUser, 'admin');
        $visibleToAccountant = InvoiceResource::getEloquentQuery()->pluck('id');
        $this->assertTrue($visibleToAccountant->contains($ownInvoice->id));
        $this->assertTrue($visibleToAccountant->contains($othersInvoice->id));
    }

    public function test_sales_agent_create_flow_forces_pending_status_and_records_the_creator(): void
    {
        $salesAgentUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        $this->grantInvoicePermissions($salesAgentUser, 'sales_agent', [
            'invoices.viewAny',
            'invoices.view',
            'invoices.create',
        ]);
        $this->actingAs($salesAgentUser, 'admin');

        $referringEmployee = Employee::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'referred_by_employee_id' => $referringEmployee->id,
            'total_amount' => 42000,
        ]);

        Livewire::test(CreateInvoice::class)
            ->assertOk()
            ->fillForm([
                'order_id' => $order->id,
                'recipient_type' => 'person',
                'recipient_name' => 'John Doe',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(InvoiceStatus::Pending, $invoice->status);
        $this->assertSame($salesAgentUser->id, $invoice->created_by);
        $this->assertNull($invoice->invoice_number);
        $this->assertSame($order->total_amount, $invoice->amount);
    }

    public function test_selecting_an_order_auto_fills_the_recipient_name_from_the_customer(): void
    {
        $salesAgentUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $salesAgentUser->id, 'role' => EmployeeRole::SalesPerson]);
        $this->grantInvoicePermissions($salesAgentUser, 'sales_agent', [
            'invoices.viewAny',
            'invoices.view',
            'invoices.create',
        ]);
        $this->actingAs($salesAgentUser, 'admin');

        $referringEmployee = Employee::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'referred_by_employee_id' => $referringEmployee->id,
        ]);

        Livewire::test(CreateInvoice::class)
            ->set('data.order_id', $order->id)
            ->assertSet('data.recipient_name', $order->customer_name);
    }
}
