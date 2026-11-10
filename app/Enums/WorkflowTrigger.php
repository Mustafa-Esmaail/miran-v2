<?php

namespace App\Enums;

enum WorkflowTrigger: string
{
    case LeadCreated = 'lead_created';
    case LeadStatusChanged = 'lead_status_changed';
    case LeadAssigned = 'lead_assigned';
    case DealCreated = 'deal_created';
    case DealStageChanged = 'deal_stage_changed';
}
