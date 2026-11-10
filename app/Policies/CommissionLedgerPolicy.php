<?php

namespace App\Policies;

use App\Models\CommissionLedger;
use App\Models\User;

class CommissionLedgerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commission_ledgers.viewAny');
    }

    public function view(User $user, CommissionLedger $commissionLedger): bool
    {
        return $user->can('commission_ledgers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('commission_ledgers.create');
    }

    public function update(User $user, CommissionLedger $commissionLedger): bool
    {
        return $user->can('commission_ledgers.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('commission_ledgers.delete');
    }

    public function delete(User $user, CommissionLedger $commissionLedger): bool
    {
        return $user->can('commission_ledgers.delete');
    }
}
