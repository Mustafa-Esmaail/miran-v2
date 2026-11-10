<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;

class CouponRepository implements CouponRepositoryInterface
{
    public function findActiveByCode(string $code): ?Coupon
    {
        return Coupon::query()->active()->where('code', $code)->first();
    }

    public function countRedemptions(int $couponId): int
    {
        return Coupon::query()->find($couponId)?->redemptions()->count() ?? 0;
    }

    public function countUserRedemptions(int $couponId, int $userId): int
    {
        return Coupon::query()->find($couponId)?->redemptions()->where('user_id', $userId)->count() ?? 0;
    }
}
