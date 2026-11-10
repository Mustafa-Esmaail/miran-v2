<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.viewAny');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('payments.delete');
    }

    public function delete(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.delete')) {
            return false;
        }

        return $payment->order?->status !== OrderStatus::Paid;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('payments.delete')) {
            return false;
        }

        return $payment->order?->status !== OrderStatus::Paid;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('payments.delete');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }
}
