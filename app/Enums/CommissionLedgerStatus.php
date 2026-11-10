<?php

namespace App\Enums;

enum CommissionLedgerStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
}
