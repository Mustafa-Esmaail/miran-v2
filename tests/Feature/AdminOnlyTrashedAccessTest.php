<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\BankTransferReceipts\Pages\ListBankTransferReceipts;
use App\Filament\Admin\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\BankTransferReceipt;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOnlyTrashedAccessTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithPermissions(User $user, array $permissions, ?string $role = null): void
    {
        $this->actingAs($user, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $user->givePermissionTo($permissions);

        if ($role) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']));
        }
    }

    public function test_service_request_soft_deletes_and_only_admin_roles_manage_trashed(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();
        $serviceRequest->delete();
        $this->assertSoftDeleted($serviceRequest);

        $regularUser = User::factory()->create();
        $this->actingAsWithPermissions($regularUser, ['service_requests.viewAny']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertTableFilterHidden('trashed');

        $admin = User::factory()->create();
        $this->actingAsWithPermissions($admin, ['service_requests.viewAny'], 'admin');

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');
    }

    public function test_coupon_soft_deletes_and_only_admin_roles_manage_trashed(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'SOFTDEL10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);
        $coupon->delete();
        $this->assertSoftDeleted($coupon);

        $regularUser = User::factory()->create();
        $this->actingAsWithPermissions($regularUser, ['coupons.viewAny']);

        Livewire::test(ListCoupons::class)
            ->assertOk()
            ->assertTableFilterHidden('trashed');

        $admin = User::factory()->create();
        $this->actingAsWithPermissions($admin, ['coupons.viewAny'], 'admin');

        Livewire::test(ListCoupons::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');
    }

    public function test_payment_soft_deletes_and_only_admin_roles_manage_trashed(): void
    {
        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-PAYMENT-SOFTDEL',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'ref-1',
            'status' => 'paid',
            'amount' => 1000,
        ]);
        $payment->delete();
        $this->assertSoftDeleted($payment);

        $regularUser = User::factory()->create();
        $this->actingAsWithPermissions($regularUser, ['payments.viewAny']);

        Livewire::test(ListPayments::class)
            ->assertOk()
            ->assertTableFilterHidden('trashed');

        $admin = User::factory()->create();
        $this->actingAsWithPermissions($admin, ['payments.viewAny'], 'admin');

        Livewire::test(ListPayments::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');
    }

    public function test_bank_transfer_receipt_soft_deletes_and_only_admin_roles_manage_trashed(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-BANKTRANSFER-SOFTDEL',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
        $receipt = BankTransferReceipt::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'receipt_path' => 'bank-receipts/test.pdf',
            'status' => 'submitted',
        ]);
        $receipt->delete();
        $this->assertSoftDeleted($receipt);

        $regularUser = User::factory()->create();
        $this->actingAsWithPermissions($regularUser, ['bank_receipts.viewAny']);

        Livewire::test(ListBankTransferReceipts::class)
            ->assertOk()
            ->assertTableFilterHidden('trashed');

        $admin = User::factory()->create();
        $this->actingAsWithPermissions($admin, ['bank_receipts.viewAny'], 'admin');

        Livewire::test(ListBankTransferReceipts::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');
    }
}
