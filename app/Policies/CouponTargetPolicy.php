<?php

namespace App\Policies;

use App\Models\CouponTarget;
use App\Models\User;

class CouponTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupon_targets.viewAny');
    }

    public function view(User $user, CouponTarget $couponTarget): bool
    {
        return $user->can('coupon_targets.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupon_targets.create');
    }

    public function update(User $user, CouponTarget $couponTarget): bool
    {
        return $user->can('coupon_targets.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('coupon_targets.delete');
    }

    public function delete(User $user, CouponTarget $couponTarget): bool
    {
        return $user->can('coupon_targets.delete');
    }
}
