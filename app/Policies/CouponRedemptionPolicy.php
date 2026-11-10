<?php

namespace App\Policies;

use App\Models\CouponRedemption;
use App\Models\User;

class CouponRedemptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupon_redemptions.viewAny');
    }

    public function view(User $user, CouponRedemption $couponRedemption): bool
    {
        return $user->can('coupon_redemptions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coupon_redemptions.create');
    }

    public function update(User $user, CouponRedemption $couponRedemption): bool
    {
        return $user->can('coupon_redemptions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('coupon_redemptions.delete');
    }

    public function delete(User $user, CouponRedemption $couponRedemption): bool
    {
        return $user->can('coupon_redemptions.delete');
    }
}
