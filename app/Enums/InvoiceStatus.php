<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Submitted = 'submitted';
}
