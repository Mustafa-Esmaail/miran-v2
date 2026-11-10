<?php

namespace App\Filament\Widgets;

use App\Enums\CommissionLedgerStatus;
use App\Enums\EmployeeRole;
use App\Models\CommissionLedger;
use App\Models\Employee;
use App\Models\SalesTarget;
use Filament\Widgets\Widget;

class SalesTargetProgressWidget extends Widget
{
    protected string $view = 'filament.widgets.crm-sales-target-progress';

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected static ?int $sort = 6;

    protected function getViewData(): array
    {
        $period = now()->format('Y-m');
        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        $targetQuery = SalesTarget::query()->with('employee')->where('period', $period);

        if ($currentEmployee?->role === EmployeeRole::SalesPerson) {
            $targetQuery->where('employee_id', $currentEmployee->id);
        } elseif ($currentEmployee?->role === EmployeeRole::TeamLead) {
            $teamIds = Employee::query()->where('team_id', $currentEmployee->team_id)->pluck('id');
            $targetQuery->whereIn('employee_id', $teamIds);
        } elseif ($currentEmployee?->role === EmployeeRole::DepartmentManager) {
            $deptIds = Employee::query()->where('department_id', $currentEmployee->department_id)->pluck('id');
            $targetQuery->whereIn('employee_id', $deptIds);
        }

        $targets = $targetQuery->get();

        $rows = $targets->map(function (SalesTarget $target): array {
            $revPct = $target->getRevenueHitPercent();
            $leadsPct = $target->getLeadsHitPercent();

            return [
                'name' => $target->employee?->name ?? '—',
                'initials' => mb_strtoupper(mb_substr($target->employee?->name ?? '?', 0, 2)),
                'revenuePct' => $revPct,
                'leadsPct' => $leadsPct,
                'actualRevenue' => $target->getActualRevenue(),
                'targetRevenue' => $target->target_revenue,
                'actualLeads' => $target->getActualLeads(),
                'targetLeads' => $target->target_leads,
                'revenueColor' => match (true) {
                    $revPct >= 100 => 'success',
                    $revPct >= 70 => 'info',
                    $revPct >= 40 => 'warning',
                    default => 'danger',
                },
            ];
        })->values()->all();

        $totalTarget = $targets->sum('target_revenue');
        $totalActual = $targets->sum(fn (SalesTarget $t): int => $t->getActualRevenue());
        $overallPct = $totalTarget > 0 ? (int) min(100, round($totalActual / $totalTarget * 100)) : 0;

        $pendingCommissions = (int) CommissionLedger::query()
            ->where('period', $period)
            ->where('status', CommissionLedgerStatus::Pending)
            ->sum('commission_amount');

        return [
            'period' => $period,
            'rows' => $rows,
            'overallPct' => $overallPct,
            'totalActual' => $totalActual,
            'totalTarget' => $totalTarget,
            'pendingCommissions' => $pendingCommissions,
        ];
    }
}
