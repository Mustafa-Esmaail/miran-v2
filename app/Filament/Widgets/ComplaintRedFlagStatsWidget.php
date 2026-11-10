<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Enums\ServiceRequestSource;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ComplaintRedFlagStatsWidget extends BaseStatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.complaint_red_flags_heading');
    }

    protected function getStats(): array
    {
        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        $baseQuery = fn (): Builder => $this->scopeComplaintQuery(
            ServiceRequest::query()->where('source', ServiceRequestSource::Complaint),
            $currentEmployee
        );

        $lateStatus = ServiceRequestStatus::query()->where('slug', 'complaint_late')->first();
        $notContactedStatus = ServiceRequestStatus::query()->where('slug', 'complaint_not_contacted')->first();

        $lateCount = ($baseQuery)()->where('status', 'complaint_late')->count();
        $notContactedCount = ($baseQuery)()->where('status', 'complaint_not_contacted')->count();
        $openCount = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'open'))->count();
        $resolvedCount = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->whereIn('outcome', ['won', 'lost']))->count();

        return [
            Stat::make(__('admin.dashboard_widgets.stats.complaint_late'), $lateCount)
                ->icon('heroicon-o-flag')
                ->color(Color::hex($lateStatus?->color ?? '#ef4444')),
            Stat::make(__('admin.dashboard_widgets.stats.complaint_not_contacted'), $notContactedCount)
                ->icon('heroicon-o-flag')
                ->color(Color::hex($notContactedStatus?->color ?? '#ef4444')),
            Stat::make(__('admin.dashboard_widgets.stats.complaint_open'), $openCount)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('admin.dashboard_widgets.stats.complaint_resolved'), $resolvedCount)
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }

    private function scopeComplaintQuery(Builder $query, ?Employee $employee): Builder
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
