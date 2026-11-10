<?php

namespace App\Enums;

enum ServiceRequestSource: string
{
    case Lead = 'lead';
    case Contact = 'contact';
    case CorporateTraining = 'corporate_training';
    case Complaint = 'complaint';
    case Form = 'form';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Manual = 'manual';
    case LandingPage = 'landing_page';
}
