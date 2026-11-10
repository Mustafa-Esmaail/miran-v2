<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Enums\ServiceRequestSource;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmSourceBreakdownWidget extends BaseStatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_source_heading');
    }

    protected function getStats(): array
    {
        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        $baseQuery = fn () => $this->scopeLeadQuery(ServiceRequest::query(), $currentEmployee);

        $total = ($baseQuery)()->count();

        $pct = fn (int $n): string => $total > 0 ? round($n / $total * 100).'% '.__('admin.dashboard_widgets.stats.of_total') : '0%';

        $sourceColors = [
            ServiceRequestSource::Lead->value => 'info',
            ServiceRequestSource::Contact->value => 'primary',
            ServiceRequestSource::CorporateTraining->value => 'warning',
            ServiceRequestSource::Complaint->value => 'danger',
        ];

        $sourceIcons = [
            ServiceRequestSource::Lead->value => 'heroicon-o-user-plus',
            ServiceRequestSource::Contact->value => 'heroicon-o-phone',
            ServiceRequestSource::CorporateTraining->value => 'heroicon-o-building-office-2',
            ServiceRequestSource::Complaint->value => 'heroicon-o-exclamation-triangle',
        ];

        $stats = [];
        foreach (ServiceRequestSource::cases() as $source) {
            $count = ($baseQuery)()->where('source', $source->value)->count();
            $stats[] = Stat::make(__('admin.options.'.$source->value), $count)
                ->description($pct($count))
                ->icon($sourceIcons[$source->value] ?? 'heroicon-o-tag')
                ->color($sourceColors[$source->value] ?? 'gray');
        }

        $stats[] = Stat::make(__('admin.dashboard_widgets.stats.source_total'), $total)
            ->icon('heroicon-o-chart-bar')
            ->color('gray');

        return $stats;
    }

    private function scopeLeadQuery(\Illuminate\Database\Eloquent\Builder $query, ?Employee $employee): \Illuminate\Database\Eloquent\Builder
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
}
