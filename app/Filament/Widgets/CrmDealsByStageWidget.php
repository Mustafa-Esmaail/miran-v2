<?php

namespace App\Filament\Widgets;

use App\Enums\DealStage;
use App\Enums\EmployeeRole;
use App\Models\Deal;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmDealsByStageWidget extends BaseStatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_deals_stage_heading', ['quarter' => 'Q'.ceil(now()->month / 3).' '.now()->year]);
    }

    protected function getStats(): array
    {
        $quarterStart = now()->startOfQuarter();
        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        $baseQuery = fn () => $this->scopeDealQuery(Deal::query(), $currentEmployee);

        $activeStages = [
            DealStage::Prospecting,
            DealStage::Qualification,
            DealStage::Proposal,
            DealStage::Negotiation,
        ];

        $stageColors = [
            DealStage::Prospecting->value => 'gray',
            DealStage::Qualification->value => 'info',
            DealStage::Proposal->value => 'primary',
            DealStage::Negotiation->value => 'warning',
        ];

        $stats = [];

        foreach ($activeStages as $stage) {
            $stageDeals = ($baseQuery)()->where('stage', $stage->value);
            $count = (clone $stageDeals)->count();
            $value = (clone $stageDeals)->sum('value');

            $stats[] = Stat::make(
                __('admin.options.'.$stage->value),
                'SAR '.number_format($value / 100, 0)
            )
                ->description($count.' '.__('admin.dashboard_widgets.stats.deals'))
                ->icon('heroicon-o-briefcase')
                ->color($stageColors[$stage->value] ?? 'gray');
        }

        $wonValue = ($baseQuery)()
            ->where('stage', DealStage::ClosedWon->value)
            ->where('updated_at', '>=', $quarterStart)
            ->sum('value');

        $wonCount = ($baseQuery)()
            ->where('stage', DealStage::ClosedWon->value)
            ->where('updated_at', '>=', $quarterStart)
            ->count();

        $lostCount = ($baseQuery)()
            ->where('stage', DealStage::ClosedLost->value)
            ->where('updated_at', '>=', $quarterStart)
            ->count();

        $stats[] = Stat::make(__('admin.dashboard_widgets.stats.won_this_quarter'), 'SAR '.number_format($wonValue / 100, 0))
            ->description($wonCount.' '.__('admin.dashboard_widgets.stats.deals'))
            ->icon('heroicon-o-check-circle')
            ->color('success');

        $stats[] = Stat::make(__('admin.dashboard_widgets.stats.lost_this_quarter'), $lostCount)
            ->description(__('admin.dashboard_widgets.stats.deals_closed_lost'))
            ->icon('heroicon-o-x-circle')
            ->color($lostCount > 0 ? 'danger' : 'gray');

        return $stats;
    }

    private function scopeDealQuery(\Illuminate\Database\Eloquent\Builder $query, ?Employee $employee): \Illuminate\Database\Eloquent\Builder
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
}
