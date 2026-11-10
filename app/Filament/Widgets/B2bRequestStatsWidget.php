<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\B2bRequestsReport;
use App\Traits\HasServiceRequestReportFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class B2bRequestStatsWidget extends BaseStatsOverviewWidget
{
    use HasServiceRequestReportFilters, InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.b2b_request_stats_heading');
    }

    protected function getColumns(): int|array|null
    {
        return 6;
    }

    /**
     * New = on the default status; Open = any other open-outcome status;
     * Successful = won outcome; Cancelled = lost outcome.
     */
    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        $baseQuery = fn (): Builder => B2bRequestsReport::scopeToB2b(
            $this->filteredServiceRequestQuery($filters),
            $filters
        );

        $total = ($baseQuery)()->count();
        $new = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('is_default', true))->count();
        $open = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'open')->where('is_default', false))->count();
        $succeeded = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'won'))->count();
        $cancelled = ($baseQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'lost'))->count();
        $closed = $succeeded + $cancelled;
        $successRate = $closed > 0 ? round($succeeded / $closed * 100, 1) : 0;

        return [
            Stat::make(__('admin.dashboard_widgets.stats.b2b_total'), $total)
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_new'), $new)
                ->icon('heroicon-o-sparkles')
                ->color('warning'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_open'), $open)
                ->icon('heroicon-o-arrow-path')
                ->color('info'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_succeeded'), $succeeded)
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_cancelled'), $cancelled)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('admin.dashboard_widgets.stats.b2b_success_rate'), $successRate.'%')
                ->icon('heroicon-o-chart-pie')
                ->color('info'),
        ];
    }
}
