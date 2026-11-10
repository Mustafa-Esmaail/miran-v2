<?php

namespace App\Enums;

enum CouponTargetType: string
{
    case Course = 'course';
    case CourseGroup = 'course_group';
    case Category = 'category';
    case Bundle = 'bundle';
}
