<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OrderItem;
use App\Models\Team;
use App\Services\Settings\SettingsService;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class SalesPerformanceReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.sales-performance-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.sales_performance_report');
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
        return auth('admin')->user()?->can('sales_performance_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.sales_performance_report');
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Today;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('payment_method')
                    ->label(__('admin.labels.payment_method'))
                    ->options([
                        'bank_transfer' => __('admin.labels.bank_transfer_provider'),
                        'tamara' => 'Tamara',
                        'cod' => __('admin.labels.cod'),
                    ])
                    ->placeholder(__('admin.labels.all')),
                Select::make('department_id')
                    ->label(__('admin.labels.department'))
                    ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->live()
                    ->placeholder(__('admin.labels.all')),
                Select::make('team_id')
                    ->label(__('admin.labels.team'))
                    ->options(fn (Get $get): array => Team::query()
                        ->when($get('department_id'), fn (Builder $q, string $id): Builder => $q->where('department_id', $id))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('employee_id')
                    ->label(__('admin.labels.name'))
                    ->options(fn (): array => Employee::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->employeesQuery())
            ->recordUrl(fn (Employee $record): string => EmployeeResource::getUrl('edit', ['record' => $record->id]))
            ->columns([
                TextColumn::make('name')->label(__('admin.labels.name'))->searchable(),
                TextColumn::make('department.name')->label(__('admin.labels.department'))->toggleable(),
                TextColumn::make('completed_orders_count')
                    ->label(__('admin.labels.completed_orders'))
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->label(__('admin.labels.total'))),
                TextColumn::make('completed_orders_total')
                    ->label(__('admin.labels.total_revenue'))
                    ->formatStateUsing(fn (?string $state): string => number_format((float) $state, 2))
                    ->sortable()
                    ->summarize(Sum::make()->label(__('admin.labels.total'))->numeric(decimalPlaces: 2)),
                TextColumn::make('items_sold')
                    ->label(__('admin.labels.items_sold'))
                    ->state(fn (Employee $record): int => $this->itemsSoldBy([$record->id]))
                    ->numeric()
                    ->toggleable()
                    ->summarize(Summarizer::make()
                        ->label(__('admin.labels.total'))
                        ->using(fn (QueryBuilder $query): int => $this->itemsSoldBy($query->pluck('id')))),
                TextColumn::make('assigned_requests_count')
                    ->label(__('admin.labels.assigned_requests_count'))
                    ->numeric()
                    ->sortable()
                    ->summarize(Sum::make()->label(__('admin.labels.total'))),
                TextColumn::make('sales_conversion_rate')
                    ->label(__('admin.labels.sales_conversion_rate'))
                    ->badge()
                    ->state(fn (Employee $record): ?float => $record->assigned_requests_count > 0
                        ? ($record->completed_orders_count / $record->assigned_requests_count) * 100
                        : null)
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '—' : number_format($state, 1).'%')
                    ->color(fn (?float $state): array|string => app(SettingsService::class)->completionColorForFilament($state))
                    ->alignEnd()
                    ->summarize(Summarizer::make()
                        ->label(__('admin.labels.total'))
                        ->using(function (QueryBuilder $query): string {
                            $totalAssigned = (int) $query->sum('assigned_requests_count');
                            $totalCompleted = (int) $query->sum('completed_orders_count');

                            return $totalAssigned > 0 ? number_format(($totalCompleted / $totalAssigned) * 100, 1).'%' : '—';
                        })),
            ])
            ->defaultSort('completed_orders_count', 'desc');
    }

    /**
     * @return Builder<Employee>
     */
    private function employeesQuery(): Builder
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;
        $departmentIdFilter = $this->filters['department_id'] ?? null;
        $teamIdFilter = $this->filters['team_id'] ?? null;
        $employeeIdFilter = $this->filters['employee_id'] ?? null;
        $courseIdFilter = $this->filters['course_id'] ?? null;
        $bundleIdFilter = $this->filters['bundle_id'] ?? null;

        return Employee::query()
            ->when($departmentIdFilter, fn (Builder $q, string $id): Builder => $q->where('department_id', $id))
            ->when($teamIdFilter, fn (Builder $q, string $id): Builder => $q->where('team_id', $id))
            ->when($employeeIdFilter, fn (Builder $q, string $id): Builder => $q->where('id', $id))
            ->whereHas('referredOrders', fn (Builder $o): Builder => $this->applyOrderFilters($o))
            ->withCount(['referredOrders as completed_orders_count' => fn (Builder $q): Builder => $this->applyOrderFilters($q)])
            ->withSum(['referredOrders as completed_orders_total' => fn (Builder $q): Builder => $this->applyOrderFilters($q)], 'total_amount')
            ->withCount(['assignedServiceRequests as assigned_requests_count' => fn (Builder $q): Builder => $q
                ->when($dateFrom, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                ->when($dateTo, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))
                ->when($courseIdFilter, fn (Builder $q, string $id): Builder => $q->where('course_id', $id))
                ->when($bundleIdFilter, fn (Builder $q, string $id): Builder => $q->where('bundle_id', $id))]);
    }

    private function applyOrderFilters(Builder $query): Builder
    {
        $courseGroupIds = $this->courseGroupIdsForFilter();
        $bundleIdFilter = $this->filters['bundle_id'] ?? null;

        return $query
            ->filteredForReport(
                $this->filters['date_from'] ?? null,
                $this->filters['date_to'] ?? null,
                $this->filters['payment_method'] ?? null,
                'sales',
                OrderStatus::Paid->value,
            )
            ->when($courseGroupIds, fn (Builder $q, Collection $ids): Builder => $q->whereHas(
                'items',
                fn (Builder $i): Builder => $i->where('purchasable_type', CourseGroup::class)->whereIn('purchasable_id', $ids)
            ))
            ->when($bundleIdFilter, fn (Builder $q, string $id): Builder => $q->whereHas(
                'items',
                fn (Builder $i): Builder => $i->where('purchasable_type', Bundle::class)->where('purchasable_id', $id)
            ));
    }

    /**
     * Per-request memo — applyOrderFilters runs once per row for items_sold.
     *
     * @var array{0: ?string, 1: ?Collection}|null
     */
    private ?array $courseGroupIdsCache = null;

    private function courseGroupIdsForFilter(): ?Collection
    {
        $courseIdFilter = $this->filters['course_id'] ?? null;

        if ($this->courseGroupIdsCache === null || $this->courseGroupIdsCache[0] !== $courseIdFilter) {
            $this->courseGroupIdsCache = [
                $courseIdFilter,
                $courseIdFilter ? CourseGroup::query()->where('course_id', $courseIdFilter)->pluck('id') : null,
            ];
        }

        return $this->courseGroupIdsCache[1];
    }

    /**
     * @param  iterable<int, int|string>  $employeeIds
     */
    private function itemsSoldBy(iterable $employeeIds): int
    {
        return OrderItem::sumEffectiveQuantity(
            OrderItem::query()
                ->whereHas('order', fn (Builder $q): Builder => $this->applyOrderFilters($q)->whereIn('referred_by_employee_id', $employeeIds))
        );
    }

    protected function exportName(): string
    {
        return 'sales-performance-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.name'),
            __('admin.labels.department'),
            __('admin.labels.completed_orders'),
            __('admin.labels.total_revenue'),
            __('admin.labels.items_sold'),
            __('admin.labels.assigned_requests_count'),
            __('admin.labels.sales_conversion_rate').' (%)',
        ];
    }

    /**
     * Uses the table's filtered + sorted query so search and sort carry over.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $employees = $this->getFilteredSortedTableQuery()->with('department')->get();

        $rows = $employees->map(fn (Employee $employee): array => [
            $employee->name,
            $employee->department?->name,
            (int) $employee->completed_orders_count,
            round((float) $employee->completed_orders_total, 2),
            $this->itemsSoldBy([$employee->id]),
            (int) $employee->assigned_requests_count,
            $this->formatExportRate($employee->assigned_requests_count > 0
                ? ($employee->completed_orders_count / $employee->assigned_requests_count) * 100
                : null),
        ]);

        $totalCompleted = (int) $employees->sum('completed_orders_count');
        $totalAssigned = (int) $employees->sum('assigned_requests_count');

        return $rows
            ->push([
                __('admin.labels.total'),
                null,
                $totalCompleted,
                round((float) $employees->sum('completed_orders_total'), 2),
                $employees->isEmpty() ? 0 : $this->itemsSoldBy($employees->pluck('id')),
                $totalAssigned,
                $this->formatExportRate($totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : null),
            ])
            ->all();
    }
}
