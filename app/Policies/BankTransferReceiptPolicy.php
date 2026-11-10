<?php

namespace App\Policies;

use App\Enums\BankTransferStatus;
use App\Models\BankTransferReceipt;
use App\Models\User;

class BankTransferReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bank_receipts.viewAny');
    }

    public function view(User $user, BankTransferReceipt $receipt): bool
    {
        return $user->can('bank_receipts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bank_receipts.create');
    }

    public function update(User $user, BankTransferReceipt $receipt): bool
    {
        return $user->can('bank_receipts.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('bank_receipts.delete');
    }

    public function delete(User $user, BankTransferReceipt $receipt): bool
    {
        if (! $user->can('bank_receipts.delete')) {
            return false;
        }

        return $receipt->status === BankTransferStatus::Submitted;
    }

    public function forceDelete(User $user, BankTransferReceipt $receipt): bool
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('bank_receipts.delete')) {
            return false;
        }

        return $receipt->status === BankTransferStatus::Submitted;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('bank_receipts.delete');
    }

    public function restore(User $user, BankTransferReceipt $receipt): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }
}
