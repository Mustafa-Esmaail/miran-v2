<?php

namespace App\Enums;

enum InvoiceRecipientType: string
{
    case Person = 'person';
    case Company = 'company';
}
