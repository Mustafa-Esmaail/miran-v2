<?php

namespace App\Observers;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;

class EmployeeObserver
{
    private const CRM_ROLES = ['crm_manager', 'department_manager', 'team_lead', 'sales_agent', 'support_agent'];

    public function created(Employee $employee): void
    {
        if (! $employee->employee_code) {
            $prefix = filled($employee->department?->code)
                ? $employee->department->code
                : 'EMP';

            $employee->updateQuietly(['employee_code' => $prefix.'-'.$employee->id]);
        }

        $this->syncSpatieRole($employee);
    }

    public function updated(Employee $employee): void
    {
        if ($employee->wasChanged('role') || $employee->wasChanged('user_id')) {
            $this->syncSpatieRole($employee);
        }
    }

    private function syncSpatieRole(Employee $employee): void
    {
        if (! $employee->user_id || ! $employee->role) {
            return;
        }

        $user = User::find($employee->user_id);

        if (! $user) {
            return;
        }

        $spatieRoleName = match ($employee->role) {
            EmployeeRole::GeneralManager => 'crm_manager',
            EmployeeRole::DepartmentManager => 'department_manager',
            EmployeeRole::TeamLead => 'team_lead',
            EmployeeRole::SalesPerson => 'sales_agent',
            EmployeeRole::SupportAgent => 'support_agent',
        };

        $role = Role::query()
            ->where('name', $spatieRoleName)
            ->where('guard_name', 'admin')
            ->first();

        if (! $role) {
            return;
        }

        // Remove existing CRM roles then assign the correct one
        $existingCrm = Role::query()
            ->whereIn('name', self::CRM_ROLES)
            ->where('guard_name', 'admin')
            ->get();

        $user->removeRole($existingCrm);
        $user->assignRole($role);
    }
}
