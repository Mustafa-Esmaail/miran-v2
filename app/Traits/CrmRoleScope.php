<?php

namespace App\Traits;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

trait CrmRoleScope
{
    protected function currentEmployee(): ?Employee
    {
        $userId = auth()->user()?->getKey();

        return $userId ? Employee::query()->where('user_id', $userId)->first() : null;
    }

    /**
     * Employees the given CRM employee may see or act for: agents only
     * themselves, team leads their team, department managers their
     * department, general managers (and users without a CRM role) everyone.
     */
    protected function scopeEmployees(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->whereKey($employee->id),
            EmployeeRole::TeamLead => $query->where('team_id', $employee->team_id),
            EmployeeRole::DepartmentManager => $query->where('department_id', $employee->department_id),
            EmployeeRole::GeneralManager => $query,
        };
    }

    protected function scopeLeads(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('assigned_to', $employee->user_id),
            EmployeeRole::TeamLead => $query->whereIn(
                'assigned_to',
                Employee::query()->where('team_id', $employee->team_id)->whereNotNull('user_id')->pluck('user_id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'assigned_to',
                Employee::query()->where('department_id', $employee->department_id)->whereNotNull('user_id')->pluck('user_id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }

    protected function scopeDeals(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('employee_id', $employee->id),
            EmployeeRole::TeamLead => $query->whereIn(
                'employee_id',
                Employee::query()->where('team_id', $employee->team_id)->pluck('id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'employee_id',
                Employee::query()->where('department_id', $employee->department_id)->pluck('id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }

    protected function scopeActivities(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('employee_id', $employee->id),
            EmployeeRole::TeamLead => $query->whereIn(
                'employee_id',
                Employee::query()->where('team_id', $employee->team_id)->pluck('id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'employee_id',
                Employee::query()->where('department_id', $employee->department_id)->pluck('id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }

    protected function scopeCompanies(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('account_manager_id', $employee->id),
            EmployeeRole::TeamLead => $query->whereIn(
                'account_manager_id',
                Employee::query()->where('team_id', $employee->team_id)->pluck('id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'account_manager_id',
                Employee::query()->where('department_id', $employee->department_id)->pluck('id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }

    protected function scopeOrders(Builder $query, ?Employee $employee): Builder
    {
        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('referred_by_employee_id', $employee->id),
            EmployeeRole::TeamLead => $query->whereIn(
                'referred_by_employee_id',
                Employee::query()->where('team_id', $employee->team_id)->pluck('id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'referred_by_employee_id',
                Employee::query()->where('department_id', $employee->department_id)->pluck('id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }
}
