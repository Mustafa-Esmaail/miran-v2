<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\OrderItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrdersReportTopItemsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected string $view = 'filament.widgets.orders-report-top-items';

    public function getHeading(): string
    {
        return __('admin.dashboard_widgets.orders_report_top_items_heading');
    }

    public function getTopItems(): Collection
    {
        $filters = $this->pageFilters ?? [];
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $paymentMethod = $filters['payment_method'] ?? null;

        $rows = OrderItem::query()
            ->selectRaw('purchasable_type, purchasable_id, SUM(quantity) as total_quantity, COUNT(DISTINCT order_id) as orders_count')
            ->whereHas('order', fn (Builder $query) => $query
                ->filteredForReport($dateFrom, $dateTo, $paymentMethod)
                ->where('status', OrderStatus::Paid))
            ->groupBy('purchasable_type', 'purchasable_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $locale = app()->getLocale();

        return $rows->map(function (OrderItem $row) use ($locale): array {
            $title = '—';

            if ($row->purchasable_type === CourseGroup::class) {
                $group = CourseGroup::query()->with('course')->find($row->purchasable_id);
                $courseTitle = $group?->course?->getTranslation('title', $locale);
                $title = $courseTitle
                    ? $courseTitle.' — '.$group->getTranslation('title', $locale)
                    : '—';
            } elseif ($row->purchasable_type === Bundle::class) {
                $bundle = Bundle::query()->find($row->purchasable_id);
                $title = $bundle?->getTranslation('title', $locale) ?? '—';
            }

            return [
                'title' => $title,
                'type' => $row->purchasable_type === Bundle::class
                    ? __('admin.options.bundle')
                    : __('admin.options.course_group'),
                'quantity' => (int) $row->total_quantity,
                'orders_count' => (int) $row->orders_count,
            ];
        });
    }
}
