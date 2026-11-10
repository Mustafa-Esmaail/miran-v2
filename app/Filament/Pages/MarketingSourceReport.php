<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Services\Settings\SettingsService;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MarketingSourceReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 18;

    protected string $view = 'filament.pages.marketing-source-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.marketing_source_report');
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
        return auth('admin')->user()?->can('marketing_source_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.marketing_source_report');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                ...$this->dateRangeFields(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.labels.marketing_source')),
                TextColumn::make('requests_count')
                    ->label(__('admin.labels.service_requests_count'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('paid_orders_count')
                    ->label(__('admin.labels.paid_orders_count'))
                    ->numeric()
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
     * @return array{sources_count: int, requests_total: int, paid_total: int, avg_rate: ?float}
     */
    public function getReportTotals(): array
    {
        $rows = $this->rows();

        $requestsTotal = (int) $rows->sum('requests_count');
        $paidTotal = (int) $rows->sum('paid_orders_count');

        return [
            'sources_count' => $rows->count(),
            'requests_total' => $requestsTotal,
            'paid_total' => $paidTotal,
            'avg_rate' => $requestsTotal > 0 ? ($paidTotal / $requestsTotal) * 100 : null,
        ];
    }

    protected function exportName(): string
    {
        return 'marketing-source-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.marketing_source'),
            __('admin.labels.service_requests_count'),
            __('admin.labels.paid_orders_count'),
            __('admin.labels.conversion_rate').' (%)',
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $totals = $this->getReportTotals();

        return $this->rows()
            ->map(fn (array $row): array => [
                $row['label'],
                $row['requests_count'],
                $row['paid_orders_count'],
                $this->formatExportRate($row['conversion_rate']),
            ])
            ->push([
                __('admin.labels.total'),
                $totals['requests_total'],
                $totals['paid_total'],
                $this->formatExportRate($totals['avg_rate']),
            ])
            ->all();
    }

    private ?Collection $rowsCache = null;

    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows();
    }

    /**
     * @return Collection<int, array{marketing_source: string, label: string, requests_count: int, paid_orders_count: int, conversion_rate: ?float}>
     */
    protected function buildRows(): Collection
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        $requestCounts = $this->countBySource(
            ServiceRequest::query()
                ->when($dateFrom, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                ->when($dateTo, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))
        );

        $paidOrderCounts = $this->countBySource(
            Order::query()->filteredForReport($dateFrom, $dateTo, null, null, OrderStatus::Paid->value)
        );

        $sources = collect(array_keys($requestCounts))->merge(array_keys($paidOrderCounts))->unique();

        $rows = $sources->map(fn (string $source): array => $this->buildRow(
            $source,
            $requestCounts[$source] ?? 0,
            $paidOrderCounts[$source] ?? 0,
        ));

        return $rows->sortByDesc('requests_count')->values();
    }

    /**
     * Groups by marketing_source, folding NULL and '' into the same bucket —
     * MySQL treats them as distinct GROUP BY keys, but both mean "no source".
     *
     * @return array<string, int>
     */
    private function countBySource(Builder $query): array
    {
        $counts = [];

        foreach ($query->selectRaw('marketing_source, count(*) as aggregate')->groupBy('marketing_source')->get() as $row) {
            $key = $row->marketing_source ?: '';
            $counts[$key] = ($counts[$key] ?? 0) + (int) $row->aggregate;
        }

        return $counts;
    }

    /**
     * @return array{marketing_source: string, label: string, requests_count: int, paid_orders_count: int, conversion_rate: ?float}
     */
    private function buildRow(string $source, int $requestsCount, int $paidOrdersCount): array
    {
        return [
            'marketing_source' => $source,
            'label' => $source === '' ? __('admin.labels.unknown') : $source,
            'requests_count' => $requestsCount,
            'paid_orders_count' => $paidOrdersCount,
            'conversion_rate' => $requestsCount > 0 ? ($paidOrdersCount / $requestsCount) * 100 : null,
        ];
    }
}
