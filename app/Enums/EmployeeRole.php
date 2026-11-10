<?php

namespace App\Enums;

enum EmployeeRole: string
{
    case SalesPerson = 'sales_person';
    case TeamLead = 'team_lead';
    case DepartmentManager = 'department_manager';
    case GeneralManager = 'sales_manager';
    case SupportAgent = 'support_agent';
}
