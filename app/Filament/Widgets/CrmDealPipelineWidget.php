<?php

namespace App\Filament\Widgets;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Traits\CrmRoleScope;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmDealPipelineWidget extends BaseStatsOverviewWidget
{
    use CrmRoleScope;

    protected int|string|array $columnSpan = ['default' => 12, 'xl' => 6];

    protected bool $contained = true;

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_deals_heading');
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $employee = $this->currentEmployee();

        $activeStages = [
            DealStage::Prospecting->value,
            DealStage::Qualification->value,
            DealStage::Proposal->value,
            DealStage::Negotiation->value,
        ];

        $base = fn () => $this->scopeDeals(Deal::query(), $employee);

        $activePipelineValue = ($base)()->whereIn('stage', $activeStages)->sum('value');
        $activePipelineCount = ($base)()->whereIn('stage', $activeStages)->count();
        $wonThisMonth = ($base)()->where('stage', DealStage::ClosedWon->value)->where('updated_at', '>=', $monthStart)->sum('value');
        $overdueCount = ($base)()->whereIn('stage', $activeStages)->whereNotNull('expected_close_date')->where('expected_close_date', '<', now()->toDateString())->count();

        // Sparkline: won deals value per day last 7 days (summed)
        $sparkWonRaw = $this->scopeDeals(Deal::query(), $employee)
            ->where('stage', DealStage::ClosedWon->value)
            ->where('updated_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(updated_at) as day, SUM(value) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $sparkWon = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $sparkWon[] = (int) $sparkWonRaw->get($day, 0);
        }

        return [
            Stat::make(__('admin.dashboard_widgets.stats.active_pipeline'), 'SAR '.number_format($activePipelineValue / 100, 0))
                ->description($activePipelineCount.' '.__('admin.dashboard_widgets.stats.active_deals'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make(__('admin.dashboard_widgets.stats.won_this_month'), 'SAR '.number_format($wonThisMonth / 100, 0))
                ->chart($sparkWon)
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make(__('admin.dashboard_widgets.stats.overdue_deals'), $overdueCount)
                ->description(__('admin.dashboard_widgets.stats.overdue_deals_desc'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->icon('heroicon-o-clock')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),

            Stat::make(__('admin.dashboard_widgets.stats.avg_deal_value'), $activePipelineCount > 0
                ? 'SAR '.number_format($activePipelineValue / $activePipelineCount / 100, 0)
                : 'SAR 0'
            )
                ->description(__('admin.dashboard_widgets.stats.avg_deal_value_desc'))
                ->descriptionIcon('heroicon-m-calculator')
                ->icon('heroicon-o-chart-pie')
                ->color('info'),
        ];
    }
}
