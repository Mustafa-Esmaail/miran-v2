<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceRequest;
use App\Services\Settings\SettingsService;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CoursePerformanceReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.course-performance-report';

    /**
     * Service request sources excluded from the "requests received" count —
     * complaints aren't sales leads, and corporate/B2B training enquiries
     * follow a separate sales motion from individual course/bundle demand.
     */
    private const EXCLUDED_SOURCES = [
        ServiceRequestSource::Complaint->value,
        ServiceRequestSource::CorporateTraining->value,
    ];

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_performance_report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.commerce');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.reports');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('course_performance_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.course_performance_report');
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Today;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('course_id')
                    ->label(__('admin.labels.course'))
                    ->options(fn (): array => Course::query()->get()->pluck('title', 'id')->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('bundle_id')
                    ->label(__('admin.labels.bundle'))
                    ->options(fn (): array => Bundle::query()->get()->pluck('title', 'id')->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('order_source')
                    ->label(__('admin.labels.order_source'))
                    ->options([
                        OrderSource::Site->value => __('admin.options.order_source_site'),
                        OrderSource::Store->value => __('admin.options.order_source_store'),
                        OrderSource::Sales->value => __('admin.options.order_source_sales'),
                        OrderSource::Admin->value => __('admin.options.order_source_admin'),
                    ])
                    ->placeholder(__('admin.labels.all')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('type')
                    ->label(__('admin.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('admin.options.'.$state))
                    ->color(fn (string $state): string => $state === 'bundle' ? 'warning' : 'info'),
                TextColumn::make('title')
                    ->label(__('admin.labels.item')),
                TextColumn::make('requests_count')
                    ->label(__('admin.labels.service_requests_count'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('paid_order_items_count')
                    ->label(__('admin.labels.paid_order_items_count'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('paid_orders_total_amount')
                    ->label(__('admin.labels.total_revenue'))
                    ->formatStateUsing(fn (int $state): string => number_format($state, 2))
                    ->alignEnd(),
                TextColumn::make('conversion_rate')
                    ->label(__('admin.labels.conversion_rate'))
                    ->badge()
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '—' : number_format($state, 1).'%')
                    ->color(fn (?float $state): array|string => app(SettingsService::class)->completionColorForFilament($state))
                    ->alignEnd(),
            ]);
    }

    /**
     * @return array{courses_count: int, requests_total: int, paid_total: int, paid_total_amount: int, avg_rate: ?float}
     */
    public function getReportTotals(): array
    {
        $rows = $this->rows();

        $requestsTotal = (int) $rows->sum('requests_count');
        $paidTotal = $this->distinctPaidOrdersQuery()->count();
        $paidTotalAmount = (int) $this->distinctPaidOrdersQuery()->sum('total_amount');

        return [
            'courses_count' => $rows->count(),
            'requests_total' => $requestsTotal,
            'paid_total' => $paidTotal,
            'paid_total_amount' => $paidTotalAmount,
            'avg_rate' => $requestsTotal > 0 ? ($paidTotal / $requestsTotal) * 100 : null,
        ];
    }

    protected function exportName(): string
    {
        return 'course-performance-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.type'),
            __('admin.labels.item'),
            __('admin.labels.service_requests_count'),
            __('admin.labels.paid_order_items_count'),
            __('admin.labels.total_revenue'),
            __('admin.labels.conversion_rate').' (%)',
        ];
    }

    /**
     * Totals use distinct paid orders (see getReportTotals), so the totals
     * row isn't a plain column sum.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $totals = $this->getReportTotals();

        return $this->rows()
            ->map(fn (array $row): array => [
                __('admin.options.'.$row['type']),
                $row['title'],
                $row['requests_count'],
                $row['paid_order_items_count'],
                $row['paid_orders_total_amount'],
                $this->formatExportRate($row['conversion_rate']),
            ])
            ->push([
                __('admin.labels.total'),
                null,
                $totals['requests_total'],
                $totals['paid_total'],
                $totals['paid_total_amount'],
                $this->formatExportRate($totals['avg_rate']),
            ])
            ->all();
    }

    /**
     * Distinct paid orders behind the report's course/bundle rows. A single
     * order can contain items for more than one course (or a course and a
     * bundle), so counts/amounts can't be summed across rows — that
     * double-counts any order that spans multiple rows. The amount uses
     * `orders.total_amount` (after discounts/coupons), same as the Orders Report.
     *
     * @return Builder<Order>
     */
    private function distinctPaidOrdersQuery(): Builder
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;
        $courseIdFilter = $this->filters['course_id'] ?? null;
        $bundleIdFilter = $this->filters['bundle_id'] ?? null;
        $orderSourceFilter = $this->filters['order_source'] ?? null;

        $courseGroupIdsForFilter = $this->resolveCourseGroupIdsForFilter($courseIdFilter);

        return Order::query()
            ->filteredForReport($dateFrom, $dateTo, null, $orderSourceFilter, OrderStatus::Paid->value)
            ->whereHas('items', function (Builder $query) use ($courseGroupIdsForFilter, $bundleIdFilter): void {
                $query->where(function (Builder $query) use ($courseGroupIdsForFilter, $bundleIdFilter): void {
                    $query->where(function (Builder $query) use ($courseGroupIdsForFilter): void {
                        $query->where('purchasable_type', CourseGroup::class)
                            ->when($courseGroupIdsForFilter, fn (Builder $query, Collection $ids): Builder => $query->whereIn('purchasable_id', $ids));
                    })->orWhere(function (Builder $query) use ($bundleIdFilter): void {
                        $query->where('purchasable_type', Bundle::class)
                            ->when($bundleIdFilter, fn (Builder $query, string $id): Builder => $query->where('purchasable_id', $id));
                    });
                });
            });
    }

    /**
     * Revenue per purchasable, based on what the customer actually paid
     * (`orders.total_amount`, net of discounts/coupons). Each order's total
     * is split across its items in proportion to their line value
     * (price × quantity) against the order subtotal.
     *
     * @param  Builder<OrderItem>  $query
     * @return Collection<int|string, int>
     */
    private function allocatedPaidAmountsByPurchasable(Builder $query): Collection
    {
        return $query
            ->with('order:id,subtotal_amount,total_amount')
            ->get(['id', 'order_id', 'purchasable_id', 'price_amount', 'quantity'])
            ->groupBy('purchasable_id')
            ->map(fn (Collection $items): int => (int) round($items->sum(function (OrderItem $item): float {
                $subtotal = (int) $item->order->subtotal_amount;

                if ($subtotal <= 0) {
                    return 0;
                }

                return $item->order->total_amount * ($item->price_amount * $item->quantity) / $subtotal;
            })));
    }

    private function resolveCourseGroupIdsForFilter(?string $courseIdFilter): ?Collection
    {
        return $courseIdFilter
            ? CourseGroup::query()->where('course_id', $courseIdFilter)->pluck('id')
            : null;
    }

    private ?Collection $rowsCache = null;

    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows();
    }

    /**
     * @return Collection<int, array{type: string, title: string, requests_count: int, paid_order_items_count: int, paid_orders_total_amount: int, conversion_rate: ?float}>
     */
    protected function buildRows(): Collection
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;
        $courseIdFilter = $this->filters['course_id'] ?? null;
        $bundleIdFilter = $this->filters['bundle_id'] ?? null;
        $orderSourceFilter = $this->filters['order_source'] ?? null;

        $requestsBaseQuery = ServiceRequest::query()
            ->whereNotIn('source', self::EXCLUDED_SOURCES)
            ->when($dateFrom, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when($dateTo, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date));

        $courseRequestCounts = (clone $requestsBaseQuery)
            ->whereNotNull('course_id')
            ->when($courseIdFilter, fn (Builder $q, string $id): Builder => $q->where('course_id', $id))
            ->selectRaw('course_id, count(*) as aggregate')
            ->groupBy('course_id')
            ->pluck('aggregate', 'course_id');

        $bundleRequestCounts = (clone $requestsBaseQuery)
            ->whereNotNull('bundle_id')
            ->when($bundleIdFilter, fn (Builder $q, string $id): Builder => $q->where('bundle_id', $id))
            ->selectRaw('bundle_id, count(*) as aggregate')
            ->groupBy('bundle_id')
            ->pluck('aggregate', 'bundle_id');

        // Course purchases are recorded against the specific CourseGroup bought,
        // not the parent Course — roll group-level paid-order counts up to the course.
        $courseGroupIdsForFilter = $this->resolveCourseGroupIdsForFilter($courseIdFilter);

        $courseGroupOrderCounts = OrderItem::query()
            ->where('purchasable_type', CourseGroup::class)
            ->when($courseGroupIdsForFilter, fn (Builder $q, Collection $ids): Builder => $q->whereIn('purchasable_id', $ids))
            ->whereHas('order', fn (Builder $o): Builder => $o->filteredForReport($dateFrom, $dateTo, null, $orderSourceFilter, OrderStatus::Paid->value))
            ->selectRaw('purchasable_id, count(*) as aggregate')
            ->groupBy('purchasable_id')
            ->pluck('aggregate', 'purchasable_id');

        $bundleOrderCounts = OrderItem::query()
            ->where('purchasable_type', Bundle::class)
            ->when($bundleIdFilter, fn (Builder $q, string $id): Builder => $q->where('purchasable_id', $id))
            ->whereHas('order', fn (Builder $o): Builder => $o->filteredForReport($dateFrom, $dateTo, null, $orderSourceFilter, OrderStatus::Paid->value))
            ->selectRaw('purchasable_id, count(*) as aggregate')
            ->groupBy('purchasable_id')
            ->pluck('aggregate', 'purchasable_id');

        $courseGroupOrderAmounts = $this->allocatedPaidAmountsByPurchasable(
            OrderItem::query()
                ->where('purchasable_type', CourseGroup::class)
                ->when($courseGroupIdsForFilter, fn (Builder $q, Collection $ids): Builder => $q->whereIn('purchasable_id', $ids))
                ->whereHas('order', fn (Builder $o): Builder => $o->filteredForReport($dateFrom, $dateTo, null, $orderSourceFilter, OrderStatus::Paid->value))
        );

        $bundleOrderAmounts = $this->allocatedPaidAmountsByPurchasable(
            OrderItem::query()
                ->where('purchasable_type', Bundle::class)
                ->when($bundleIdFilter, fn (Builder $q, string $id): Builder => $q->where('purchasable_id', $id))
                ->whereHas('order', fn (Builder $o): Builder => $o->filteredForReport($dateFrom, $dateTo, null, $orderSourceFilter, OrderStatus::Paid->value))
        );

        $courseGroupToCourse = CourseGroup::query()
            ->whereIn('id', $courseGroupOrderCounts->keys())
            ->pluck('course_id', 'id');

        $courseOrderCounts = collect();

        foreach ($courseGroupOrderCounts as $groupId => $count) {
            $courseId = $courseGroupToCourse->get($groupId);

            if ($courseId === null) {
                continue;
            }

            $courseOrderCounts[$courseId] = ($courseOrderCounts[$courseId] ?? 0) + $count;
        }

        $courseOrderAmounts = collect();

        foreach ($courseGroupOrderAmounts as $groupId => $amount) {
            $courseId = $courseGroupToCourse->get($groupId);

            if ($courseId === null) {
                continue;
            }

            $courseOrderAmounts[$courseId] = ($courseOrderAmounts[$courseId] ?? 0) + $amount;
        }

        $courseIds = $courseIdFilter
            ? collect([$courseIdFilter])
            : $courseRequestCounts->keys()->merge($courseOrderCounts->keys())->unique();

        $bundleIds = $bundleIdFilter
            ? collect([$bundleIdFilter])
            : $bundleRequestCounts->keys()->merge($bundleOrderCounts->keys())->unique();

        $courseTitles = Course::query()->whereIn('id', $courseIds)->get()->pluck('title', 'id');
        $bundleTitles = Bundle::query()->whereIn('id', $bundleIds)->get()->pluck('title', 'id');

        $rows = collect();

        foreach ($courseIds as $courseId) {
            $rows->push($this->buildRow(
                'course',
                $courseTitles->get($courseId) ?? '—',
                (int) ($courseRequestCounts->get($courseId) ?? 0),
                (int) ($courseOrderCounts->get($courseId) ?? 0),
                (int) ($courseOrderAmounts->get($courseId) ?? 0),
            ));
        }

        foreach ($bundleIds as $bundleId) {
            $rows->push($this->buildRow(
                'bundle',
                $bundleTitles->get($bundleId) ?? '—',
                (int) ($bundleRequestCounts->get($bundleId) ?? 0),
                (int) ($bundleOrderCounts->get($bundleId) ?? 0),
                (int) ($bundleOrderAmounts->get($bundleId) ?? 0),
            ));
        }

        return $rows->sortByDesc('requests_count')->values();
    }

    /**
     * @return array{type: string, title: string, requests_count: int, paid_order_items_count: int, paid_orders_total_amount: int, conversion_rate: ?float}
     */
    private function buildRow(string $type, string $title, int $requestsCount, int $paidOrderItemsCount, int $paidOrdersTotalAmount): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'requests_count' => $requestsCount,
            'paid_order_items_count' => $paidOrderItemsCount,
            'paid_orders_total_amount' => $paidOrdersTotalAmount,
            'conversion_rate' => $requestsCount > 0 ? ($paidOrderItemsCount / $requestsCount) * 100 : null,
        ];
    }
}
