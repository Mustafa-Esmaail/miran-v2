<?php

namespace App\Support;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Traits\CrmRoleScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Static access to the CRM role scope for code that has no instance to mix
 * the trait into, such as static Filament form/table/resource configurators.
 */
class CrmScope
{
    use CrmRoleScope;

    /**
     * The Employee record of the signed-in user, if any.
     */
    public static function viewer(): ?Employee
    {
        return (new self)->currentEmployee();
    }

    /**
     * Employees the signed-in user may see or act for, per their CRM role.
     */
    public static function visibleEmployees(): Builder
    {
        $scope = new self;

        return $scope->scopeEmployees(Employee::query(), $scope->currentEmployee());
    }

    /**
     * Whether the signed-in user is an agent who may only act for themselves.
     */
    public static function isLimitedToSelf(): bool
    {
        return in_array(static::viewer()?->role, [EmployeeRole::SalesPerson, EmployeeRole::SupportAgent], true);
    }
}
