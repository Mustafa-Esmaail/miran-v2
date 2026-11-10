<?php

namespace App\Services;

use App\Enums\BankTransferStatus;
use App\Models\BankTransferReceipt;
use App\Models\Order;
use App\Repositories\Contracts\BankTransferReceiptRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;

class BankTransferService
{
    public function __construct(
        private readonly BankTransferReceiptRepositoryInterface $receipts,
        private readonly NotificationService $notifications,
        private readonly OrderApprovalService $orderApproval,
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderSeatService $seats,
    ) {}

    public function submitReceipt(Order $order, int $userId, string $path): void
    {
        $this->receipts->createReceipt([
            'order_id' => $order->id,
            'user_id' => $userId,
            'receipt_path' => $path,
            'status' => BankTransferStatus::Submitted,
        ]);

        $this->notifications->notifyBankReceiptSubmitted($order);
    }

    public function approveReceipt(int $receiptId, int $reviewerId, ?string $notes = null): void
    {
        $admin = auth('admin')->user();
        abort_unless(! $admin || $admin->can('bank_receipts.update'), 403);

        $receipt = $this->receipts->findById($receiptId);
        if (! $receipt) {
            return;
        }

        $this->receipts->updateStatus($receipt, [
            'status' => BankTransferStatus::Approved,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $order = $receipt->order;
        if ($order) {
            $this->orderApproval->approve($order);
            $this->notifications->notifyBankReceiptApproved($order);
        }
    }

    public function replaceReceipt(int $receiptId, int $adminId, string $newPath, ?string $notes = null): BankTransferReceipt
    {
        $admin = auth('admin')->user();
        abort_unless(! $admin || $admin->can('bank_receipts.replace'), 403);

        $old = $this->receipts->findById($receiptId);
        abort_if(! $old, 404);

        $new = $this->receipts->createReceipt([
            'order_id' => $old->order_id,
            'user_id' => $old->user_id,
            'receipt_path' => $newPath,
            'status' => BankTransferStatus::Submitted,
        ]);

        $this->receipts->updateStatus($old, [
            'status' => BankTransferStatus::Replaced,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'review_notes' => $notes ?? 'Replaced with a corrected receipt.',
        ]);

        return $new;
    }

    public function rejectReceipt(int $receiptId, int $reviewerId, ?string $notes = null): void
    {
        $admin = auth('admin')->user();
        abort_unless(! $admin || $admin->can('bank_receipts.update'), 403);

        $receipt = $this->receipts->findById($receiptId);
        if (! $receipt) {
            return;
        }

        $this->receipts->updateStatus($receipt, [
            'status' => BankTransferStatus::Rejected,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $order = $receipt->order;
        if ($order) {
            $this->orders->markRejected($order, $notes);
            $this->seats->cancelSeatsForOrder($order);
            $this->notifications->notifyBankReceiptRejected($order);
        }
    }
}
