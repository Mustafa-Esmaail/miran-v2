<?php

namespace App\Filament\Widgets;

use App\Enums\CrmTaskPriority;
use App\Models\CrmTask;
use App\Traits\CrmRoleScope;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmMyTasksWidget extends BaseStatsOverviewWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    protected static ?int $sort = 7;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_tasks_heading');
    }

    protected function getStats(): array
    {
        $employee = $this->currentEmployee();

        $base = fn () => $this->scopeActivities(CrmTask::query()->where('is_done', false), $employee);

        $overdue = ($base)()->whereNotNull('due_at')->where('due_at', '<', now())->count();
        $dueToday = ($base)()->whereNotNull('due_at')->whereDate('due_at', now()->toDateString())->count();
        $dueThisWeek = ($base)()->whereNotNull('due_at')->whereBetween('due_at', [now(), now()->endOfWeek()])->count();
        $totalOpen = ($base)()->count();

        // Priority breakdown sparkline: [urgent, high, normal, low]
        $byPriority = ($base)()
            ->selectRaw('priority, COUNT(*) as cnt')
            ->groupBy('priority')
            ->pluck('cnt', 'priority');

        $prioritySparkline = [
            (int) $byPriority->get(CrmTaskPriority::Urgent->value, 0),
            (int) $byPriority->get(CrmTaskPriority::High->value, 0),
            (int) $byPriority->get(CrmTaskPriority::Normal->value, 0),
            (int) $byPriority->get(CrmTaskPriority::Low->value, 0),
        ];

        return [
            Stat::make(__('admin.dashboard_widgets.stats.tasks_overdue'), $overdue)
                ->description(__('admin.dashboard_widgets.stats.tasks_overdue_desc'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->icon('heroicon-o-exclamation-circle')
                ->color($overdue > 0 ? 'danger' : 'gray'),

            Stat::make(__('admin.dashboard_widgets.stats.tasks_due_today'), $dueToday)
                ->descriptionIcon('heroicon-m-calendar')
                ->icon('heroicon-o-calendar-days')
                ->color($dueToday > 0 ? 'warning' : 'gray'),

            Stat::make(__('admin.dashboard_widgets.stats.tasks_due_week'), $dueThisWeek)
                ->description(__('admin.dashboard_widgets.stats.tasks_due_week_desc'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make(__('admin.dashboard_widgets.stats.tasks_total_open'), $totalOpen)
                ->chart($prioritySparkline)
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray'),
        ];
    }
}
