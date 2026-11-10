<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Models\Employee;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderResourceRoleScopeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithOrderPermissions(User $user): void
    {
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->firstOrCreate(['name' => "orders.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['orders.viewAny', 'orders.view']);
    }

    private function makeOrder(?Employee $referredByEmployee, string $orderNo): Order
    {
        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Customer',
            'customer_phone' => '0501234567',
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'referred_by_employee_id' => $referredByEmployee?->id,
        ]);
    }

    public function test_sales_person_only_sees_orders_they_referred(): void
    {
        $ownUser = User::factory()->create();
        $ownEmployee = Employee::factory()->create(['user_id' => $ownUser->id, 'role' => EmployeeRole::SalesPerson]);
        $otherEmployee = Employee::factory()->create(['role' => EmployeeRole::SalesPerson]);

        $ownOrder = $this->makeOrder($ownEmployee, 'ORD-OWN-1');
        $otherOrder = $this->makeOrder($otherEmployee, 'ORD-OTHER-1');

        $this->actingAsWithOrderPermissions($ownUser);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee($ownOrder->order_no)
            ->assertDontSee($otherOrder->order_no);
    }

    public function test_sales_manager_sees_all_orders(): void
    {
        $managerUser = User::factory()->create();
        Employee::factory()->create(['user_id' => $managerUser->id, 'role' => EmployeeRole::GeneralManager]);
        $otherEmployee = Employee::factory()->create(['role' => EmployeeRole::SalesPerson]);

        $order = $this->makeOrder($otherEmployee, 'ORD-VISIBLE-1');

        $this->actingAsWithOrderPermissions($managerUser);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee($order->order_no);
    }

    public function test_marketing_role_sees_orders_of_every_source(): void
    {
        $marketingUser = User::factory()->create();
        $marketingRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'marketing', 'guard_name' => 'admin']);
        $marketingUser->assignRole($marketingRole);

        $storeOrder = $this->makeOrder(null, 'ORD-STORE-1');
        $storeOrder->forceFill(['source' => 'store'])->save();

        $salesEmployee = Employee::factory()->create(['role' => EmployeeRole::SalesPerson]);
        $salesOrder = $this->makeOrder($salesEmployee, 'ORD-SALES-1');
        $salesOrder->forceFill(['source' => 'sales'])->save();

        $this->actingAsWithOrderPermissions($marketingUser);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee($storeOrder->order_no)
            ->assertSee($salesOrder->order_no);
    }
}
