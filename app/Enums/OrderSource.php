<?php

namespace App\Enums;

enum OrderSource: string
{
    case Site = 'site';
    case Store = 'store';
    case Sales = 'sales';
    case Admin = 'admin';
}
