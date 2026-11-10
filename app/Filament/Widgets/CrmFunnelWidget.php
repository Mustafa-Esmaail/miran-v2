<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmFunnelWidget extends BaseStatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_funnel_heading');
    }

    protected bool $contained = true;

    protected function getStats(): array
    {
        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        $baseQuery = fn () => $this->scopeLeadQuery(ServiceRequest::query(), $currentEmployee);

        $total = ($baseQuery)()->count();

        $pct = fn (int $n): string => $total > 0 ? ' ('.round($n / $total * 100).'%)' : '';

        $statuses = ServiceRequestStatus::query()->active()->orderBy('sort_order')->get();

        $stats = $statuses->map(function (ServiceRequestStatus $status) use ($baseQuery, $pct): Stat {
            $count = ($baseQuery)()->where('status', $status->slug)->count();

            return Stat::make($status->getTranslatedName(), $count)
                ->description($pct($count))
                ->icon('heroicon-o-tag')
                ->color(Color::hex($status->color));
        })->all();

        $convertedCount = ($baseQuery)()->whereNotNull('converted_at')->count();

        $stats[] = Stat::make(__('admin.dashboard_widgets.stats.converted'), $convertedCount)
            ->description($pct($convertedCount))
            ->icon('heroicon-o-check-badge')
            ->color('success');

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
