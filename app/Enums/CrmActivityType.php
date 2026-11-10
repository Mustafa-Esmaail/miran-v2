<?php

namespace App\Enums;

enum CrmActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';
    case Note = 'note';
    case Visit = 'visit';
}
