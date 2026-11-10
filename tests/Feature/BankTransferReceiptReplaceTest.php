<?php

namespace Tests\Feature;

use App\Enums\BankTransferStatus;
use App\Models\BankTransferReceipt;
use App\Models\Order;
use App\Models\User;
use App\Services\BankTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BankTransferReceiptReplaceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(User $user): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-REPLACE-'.random_int(100000, 999999),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);
    }

    private function actingAsAdmin(bool $withReplacePermission = true): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        Permission::query()->firstOrCreate(['name' => 'bank_receipts.replace', 'guard_name' => 'admin']);

        if ($withReplacePermission) {
            $admin->givePermissionTo('bank_receipts.replace');
        }

        return $admin;
    }

    public function test_admin_can_replace_a_submitted_receipt_and_history_is_preserved(): void
    {
        $admin = $this->actingAsAdmin();
        $student = User::factory()->create();
        $order = $this->makeOrder($student);

        $original = BankTransferReceipt::create([
            'order_id' => $order->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/wrong.pdf',
            'status' => BankTransferStatus::Submitted,
        ]);

        $service = $this->app->make(BankTransferService::class);
        $new = $service->replaceReceipt($original->id, $admin->id, 'bank-receipts/correct.pdf', 'Student sent the correct receipt via WhatsApp.');

        $original->refresh();
        $this->assertSame(BankTransferStatus::Replaced, $original->status);
        $this->assertSame($admin->id, $original->reviewed_by);
        $this->assertNotNull($original->reviewed_at);
        $this->assertSame('Student sent the correct receipt via WhatsApp.', $original->review_notes);

        $this->assertSame(BankTransferStatus::Submitted, $new->status);
        $this->assertSame('bank-receipts/correct.pdf', $new->receipt_path);
        $this->assertSame($order->id, $new->order_id);
        $this->assertSame($student->id, $new->user_id);

        $this->assertSame(2, $order->bankTransferReceipts()->count());
        $this->assertSame($new->id, $order->bankTransferReceipt()->first()->id);
    }

    public function test_an_already_reviewed_receipt_can_still_be_replaced(): void
    {
        $admin = $this->actingAsAdmin();
        $student = User::factory()->create();
        $order = $this->makeOrder($student);

        $approved = BankTransferReceipt::create([
            'order_id' => $order->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/approved.pdf',
            'status' => BankTransferStatus::Approved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $service = $this->app->make(BankTransferService::class);
        $new = $service->replaceReceipt($approved->id, $admin->id, 'bank-receipts/new.pdf');

        $approved->refresh();
        $this->assertSame(BankTransferStatus::Replaced, $approved->status);
        $this->assertSame(BankTransferStatus::Submitted, $new->status);
        $this->assertSame(2, $order->bankTransferReceipts()->count());
    }

    public function test_admin_without_permission_cannot_replace_receipt(): void
    {
        $admin = $this->actingAsAdmin(withReplacePermission: false);
        $student = User::factory()->create();
        $order = $this->makeOrder($student);

        $original = BankTransferReceipt::create([
            'order_id' => $order->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/wrong.pdf',
            'status' => BankTransferStatus::Submitted,
        ]);

        $service = $this->app->make(BankTransferService::class);

        try {
            $service->replaceReceipt($original->id, $admin->id, 'bank-receipts/correct.pdf');
            $this->fail('Expected a permission denial.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(1, $order->bankTransferReceipts()->count());
    }

    public function test_two_receipts_can_coexist_for_the_same_order(): void
    {
        $student = User::factory()->create();
        $order = $this->makeOrder($student);

        BankTransferReceipt::create([
            'order_id' => $order->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/first.pdf',
            'status' => BankTransferStatus::Replaced,
        ]);

        BankTransferReceipt::create([
            'order_id' => $order->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/second.pdf',
            'status' => BankTransferStatus::Submitted,
        ]);

        $this->assertSame(2, $order->bankTransferReceipts()->count());
    }
}
