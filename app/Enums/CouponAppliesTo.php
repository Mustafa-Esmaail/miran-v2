<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case Course = 'course';
    case Bundle = 'bundle';
    case Both = 'both';
}
