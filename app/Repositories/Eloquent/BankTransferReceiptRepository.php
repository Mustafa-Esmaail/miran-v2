<?php

namespace App\Repositories\Eloquent;

use App\Enums\BankTransferStatus;
use App\Models\BankTransferReceipt;
use App\Repositories\Contracts\BankTransferReceiptRepositoryInterface;

class BankTransferReceiptRepository implements BankTransferReceiptRepositoryInterface
{
    public function createReceipt(array $data): BankTransferReceipt
    {
        return BankTransferReceipt::query()->create($data);
    }

    public function findByOrderId(int $orderId): ?BankTransferReceipt
    {
        return BankTransferReceipt::query()->where('order_id', $orderId)->latest('id')->first();
    }

    public function findById(int $receiptId): ?BankTransferReceipt
    {
        return BankTransferReceipt::query()->find($receiptId);
    }

    public function updateStatus(BankTransferReceipt $receipt, array $data): BankTransferReceipt
    {
        $receipt->update($data);

        return $receipt;
    }

    public function countPending(): int
    {
        return BankTransferReceipt::query()
            ->where('status', BankTransferStatus::Submitted)
            ->count();
    }
}
