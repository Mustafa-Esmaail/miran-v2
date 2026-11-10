<?php

namespace App\Filament\Widgets;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ServiceRequestSource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceRequest;
use App\Services\Settings\SettingsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class OrdersReportStatsWidget extends BaseStatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 12,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.orders_report_stats_heading');
    }

    protected function getColumns(): int|array|null
    {
        return 4;
    }

    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];

        $query = Order::query()->filteredForReport(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            $filters['payment_method'] ?? null,
        );

        $totalOrders = (clone $query)
            ->when($filters['source'] ?? null, fn ($q, string $source) => $q->where('source', $source))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('status', $status))
            ->count();

        $paidQuery = fn () => (clone $query)->where('status', OrderStatus::Paid);

        $paidOrders = $paidQuery()->count();

        $totalRevenue = (int) $paidQuery()->sum('total_amount');

        $sitePaidOrders = $paidQuery()->where('source', OrderSource::Site)->count();

        $storePaidOrders = $paidQuery()->where('source', OrderSource::Store)->count();

        $salesPaidOrders = $paidQuery()->where('source', OrderSource::Sales)->count();

        $siteIncome = (int) $paidQuery()->where('source', OrderSource::Site)->sum('total_amount');

        $storeIncome = (int) $paidQuery()->where('source', OrderSource::Store)->sum('total_amount');

        $salesIncome = (int) $paidQuery()->where('source', OrderSource::Sales)->sum('total_amount');

        $itemsSold = OrderItem::sumEffectiveQuantity(
            OrderItem::query()
                ->whereHas('order', fn (Builder $q): Builder => $q
                    ->filteredForReport(
                        $filters['date_from'] ?? null,
                        $filters['date_to'] ?? null,
                        $filters['payment_method'] ?? null,
                        $filters['source'] ?? null,
                    )
                    ->where('status', OrderStatus::Paid))
        );

        $leadsQuery = ServiceRequest::query()
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date));

        $totalLeads = (clone $leadsQuery)->count();

        $b2cLeads = (clone $leadsQuery)
            ->whereNotIn('source', [ServiceRequestSource::Complaint, ServiceRequestSource::CorporateTraining])
            ->count();

        $leadToPaidRate = $b2cLeads > 0 ? ($itemsSold / $b2cLeads) * 100 : 0.0;

        return [
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_total_orders'), number_format($totalOrders))
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_paid_orders'), number_format($paidOrders))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_total_revenue'), 'SAR '.number_format($totalRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_items_sold'), number_format($itemsSold))
                ->icon('heroicon-o-cube')
                ->color('warning'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_site_paid_orders'), number_format($sitePaidOrders))
                ->icon('heroicon-o-globe-alt')
                ->color('gray'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_store_paid_orders'), number_format($storePaidOrders))
                ->icon('heroicon-o-building-storefront')
                ->color('gray'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_sales_paid_orders'), number_format($salesPaidOrders))
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_site_income'), 'SAR '.number_format($siteIncome, 2))
                ->icon('heroicon-o-globe-alt')
                ->color('gray'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_store_income'), 'SAR '.number_format($storeIncome, 2))
                ->icon('heroicon-o-building-storefront')
                ->color('gray'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_sales_income'), 'SAR '.number_format($salesIncome, 2))
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_total_leads'), number_format($totalLeads))
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('info'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_b2c_leads'), number_format($b2cLeads))
                ->icon('heroicon-o-user')
                ->color('primary'),
            Stat::make(__('admin.dashboard_widgets.stats.orders_report_lead_to_paid_rate'), number_format($leadToPaidRate, 1).'%')
                ->icon('heroicon-o-arrow-trending-up')
                ->color(app(SettingsService::class)->completionColorForFilament($leadToPaidRate)),
        ];
    }
}
