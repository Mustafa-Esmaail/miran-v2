<?php

namespace App\Enums;

enum CertificateRequestStatus: string
{
    case Pending           = 'pending';
    case Approved          = 'approved';
    case Rejected          = 'rejected';
    case SentToManarCenter = 'sent_to_manar_center';
}
