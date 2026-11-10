<?php

namespace App\Repositories\Contracts;

use App\Models\Coupon;

interface CouponRepositoryInterface
{
    public function findActiveByCode(string $code): ?Coupon;

    public function countRedemptions(int $couponId): int;

    public function countUserRedemptions(int $couponId, int $userId): int;
}
