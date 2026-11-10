<?php

namespace App\Filament\Widgets;

use App\Enums\DealStage;
use App\Filament\Pages\B2bRequestsReport;
use App\Traits\HasServiceRequestReportFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class B2bDealStatsWidget extends BaseStatsOverviewWidget
{
    use HasServiceRequestReportFilters, InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.b2b_deal_stats_heading');
    }

    protected function getColumns(): int|array|null
    {
        return 4;
    }

    /**
     * Deals and quotes reached through the filtered B2B requests. Open covers
     * every stage before closing; Won/Lost are the two closed stages.
     */
    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        $requestQuery = B2bRequestsReport::scopeToB2b($this->filteredServiceRequestQuery($filters), $filters);
        $dealQuery = fn (): Builder => B2bRequestsReport::dealsForRequests($requestQuery, $filters);

        $requestsTotal = (clone $requestQuery)->count();
        $dealsTotal = ($dealQuery)()->count();
        $dealsWon = ($dealQuery)()->where('stage', DealStage::ClosedWon)->count();
        $dealsLost = ($dealQuery)()->where('stage', DealStage::ClosedLost)->count();
        $quotesTotal = B2bRequestsReport::quotesForDeals(($dealQuery)())->count();

        return [
            Stat::make(__('admin.dashboard_widgets.stats.b2b_deals_total'), $dealsTotal)
                ->icon('heroicon-o-briefcase')
                ->color('primary'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_deals_open'), $dealsTotal - $dealsWon - $dealsLost)
                ->icon('heroicon-o-arrow-path')
                ->color('info'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_deals_won'), $dealsWon)
                ->icon('heroicon-o-trophy')
                ->color('success'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_deals_lost'), $dealsLost)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_deals_per_request'), $this->ratio($dealsTotal, $requestsTotal))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_quotes_total'), $quotesTotal)
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_quotes_per_deal'), $this->ratio($quotesTotal, $dealsTotal))
                ->icon('heroicon-o-document-duplicate')
                ->color('gray'),
        ];
    }

    protected function ratio(int $count, int $of): string
    {
        return $of > 0 ? number_format($count / $of, 1) : '0';
    }
}
