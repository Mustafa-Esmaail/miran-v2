<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Traits\CrmRoleScope;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmLeadStatsWidget extends BaseStatsOverviewWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected bool $contained = true;

    protected static ?int $sort = 1;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_leads_heading', ['month' => now()->format('M Y')]);
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $employee = $this->currentEmployee();

        $base = fn () => $this->scopeLeads(ServiceRequest::query(), $employee);

        $newThisMonth = ($base)()->where('created_at', '>=', $monthStart)->count();

        $openStatuses = ServiceRequestStatus::query()->where('outcome', 'open')->pluck('slug');
        $inProgress = ($base)()->whereIn('status', $openStatuses)->count();

        $convertedThisMonth = ($base)()->whereNotNull('converted_at')->where('converted_at', '>=', $monthStart)->count();

        $closedStatuses = ServiceRequestStatus::query()->whereIn('outcome', ['won', 'lost'])->pluck('slug');
        $closedThisMonth = ($base)()
            ->whereIn('status', $closedStatuses)
            ->where('updated_at', '>=', $monthStart)
            ->count();

        $winRate = ($closedThisMonth + $convertedThisMonth) > 0
            ? (int) round($convertedThisMonth / ($closedThisMonth + $convertedThisMonth) * 100)
            : 0;

        $sparkNewRaw = $this->scopeLeads(ServiceRequest::query(), $employee)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $sparkConvRaw = $this->scopeLeads(ServiceRequest::query()->whereNotNull('converted_at'), $employee)
            ->where('converted_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(converted_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $sparkNew = [];
        $sparkConverted = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $sparkNew[] = (int) $sparkNewRaw->get($day, 0);
            $sparkConverted[] = (int) $sparkConvRaw->get($day, 0);
        }

        return [
            Stat::make(__('admin.dashboard_widgets.stats.new_leads_month'), $newThisMonth)
                ->chart($sparkNew)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make(__('admin.dashboard_widgets.stats.leads_in_progress'), $inProgress)
                ->description(__('admin.dashboard_widgets.stats.leads_in_progress_desc'))
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-funnel')
                ->color('warning'),

            Stat::make(__('admin.dashboard_widgets.stats.converted_month'), $convertedThisMonth)
                ->chart($sparkConverted)
                ->descriptionIcon('heroicon-m-check-badge')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make(__('admin.dashboard_widgets.stats.win_rate'), $winRate.'%')
                ->description(__('admin.dashboard_widgets.stats.win_rate_desc'))
                ->descriptionIcon('heroicon-m-trophy')
                ->icon('heroicon-o-trophy')
                ->color(match (true) {
                    $winRate >= 60 => 'success',
                    $winRate >= 30 => 'warning',
                    default => 'danger',
                }),
        ];
    }
}
