<?php

namespace App\Repositories\Contracts;

use App\Models\BankTransferReceipt;

interface BankTransferReceiptRepositoryInterface
{
    public function createReceipt(array $data): BankTransferReceipt;

    public function findByOrderId(int $orderId): ?BankTransferReceipt;

    public function findById(int $receiptId): ?BankTransferReceipt;

    public function updateStatus(BankTransferReceipt $receipt, array $data): BankTransferReceipt;

    public function countPending(): int;
}
