<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait ScopesServiceRequestsToEmployee
{
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    protected static function scopeToAssignedEmployee(Builder $query): Builder
    {
        $userId = auth()->id();
        $employee = Employee::query()->where('user_id', $userId)->first();

        if (! $employee?->role) {
            return $query;
        }

        return match ($employee->role) {
            EmployeeRole::SalesPerson,
            EmployeeRole::SupportAgent => $query->where('assigned_to', $userId),
            EmployeeRole::TeamLead => $query->whereIn(
                'assigned_to',
                Employee::query()
                    ->where('team_id', $employee->team_id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            ),
            EmployeeRole::DepartmentManager => $query->whereIn(
                'assigned_to',
                Employee::query()
                    ->where('department_id', $employee->department_id)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            ),
            EmployeeRole::GeneralManager => $query,
        };
    }

    /**
     * Employees selectable as an "assigned to" value, optionally scoped to a
     * team, and further restricted to the acting Team Lead's own team.
     *
     * @return array<int, string>
     */
    public static function assignedToOptions(?int $teamId): array
    {
        $currentEmployee = Employee::query()
            ->where('user_id', auth()->id())
            ->first();

        $query = Employee::query()
            ->with(['user', 'department', 'team'])
            ->whereNotNull('user_id')
            ->orderBy('name');

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($currentEmployee?->role === EmployeeRole::TeamLead) {
            $query->where('team_id', $currentEmployee->team_id);
        } elseif ($currentEmployee?->role === EmployeeRole::DepartmentManager) {
            $query->where('department_id', $currentEmployee->department_id);
        }

        return $query->get()
            ->mapWithKeys(function (Employee $employee): array {
                $parts = [$employee->name];
                if ($employee->department?->name) {
                    $parts[] = $employee->department->name;
                }
                if ($employee->team?->name) {
                    $parts[] = $employee->team->name;
                }

                return [$employee->user_id => implode(' - ', $parts)];
            })
            ->all();
    }
}
