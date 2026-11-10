<?php

namespace App\Enums;

enum BankTransferStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Replaced = 'replaced';
}
