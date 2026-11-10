<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class OrdersReportTopEmployeesWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected string $view = 'filament.widgets.orders-report-top-employees';

    public function getHeading(): string
    {
        return __('admin.dashboard_widgets.orders_report_top_employees_heading');
    }

    public function getTopEmployees(): Collection
    {
        $filters = $this->pageFilters ?? [];

        $rows = Order::query()
            ->filteredForReport(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
                $filters['payment_method'] ?? null,
            )
            ->where('status', OrderStatus::Paid)
            ->whereNotNull('referred_by_employee_id')
            ->selectRaw('referred_by_employee_id, COUNT(*) as orders_count')
            ->groupBy('referred_by_employee_id')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->with('referredByEmployee')
            ->get();

        return $rows->map(fn (Order $row): array => [
            'name' => $row->referredByEmployee?->name ?? '—',
            'orders_count' => (int) $row->orders_count,
        ]);
    }
}
