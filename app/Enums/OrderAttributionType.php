<?php

namespace App\Enums;

enum OrderAttributionType: string
{
    case Lead = 'lead';
    case Manual = 'manual';
    case CouponCommission = 'coupon_commission';
}
