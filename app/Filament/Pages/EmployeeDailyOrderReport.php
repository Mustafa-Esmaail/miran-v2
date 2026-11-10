<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\OrderStatus;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Team;
use App\Traits\CrmRoleScope;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeDailyOrderReport extends Page implements HasTable
{
    use CrmRoleScope, HasFiltersForm, HasReportExports, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
        HasFiltersForm::updatedFilters as persistFiltersInSessionOnFilterUpdate;
    }
    use HasDateRangePresets;

    private const UNASSIGNED = 'none';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.employee-daily-order-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.employee_daily_order_report');
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
        return auth('admin')->user()?->can('employee_daily_order_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.employee_daily_order_report');
    }

    /**
     * The employee columns depend on the department/team/employee_ids
     * filters, so the cached table must be rebuilt after they change.
     */
    public function updatedFilters(): void
    {
        $this->persistFiltersInSessionOnFilterUpdate();
        $this->table = $this->table($this->makeTable());
    }

    /**
     * The page used to open on the current calendar week, which no preset
     * matches; the last seven days is the closest rolling equivalent.
     */
    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Last7Days;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('source')
                    ->label(__('admin.labels.order_source'))
                    ->options([
                        'site' => __('admin.options.order_source_site'),
                        'store' => __('admin.options.order_source_store'),
                        'sales' => __('admin.options.order_source_sales'),
                    ])
                    ->placeholder(__('admin.labels.all')),
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
                Select::make('employee_ids')
                    ->label(__('admin.labels.employees'))
                    ->multiple()
                    ->options(fn (): array => $this->visibleEmployeesQuery()
                        ->orderBy('employee_code')
                        ->get()
                        ->mapWithKeys(fn (Employee $employee): array => [$employee->id => trim($employee->employee_code.' — '.$employee->name)])
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
            ]);
    }

    public function table(Table $table): Table
    {
        $employees = $this->reportEmployeesQuery()->orderBy('employee_code')->get();

        return $table
            ->records(fn (): Collection => $this->buildRows($employees))
            ->columns([
                TextColumn::make('day')->label(__('admin.labels.date')),
                ...$this->employeeColumns($employees),
            ]);
    }

    protected function exportName(): string
    {
        return 'employee-daily-orders';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.date'),
            ...$this->reportEmployeesQuery()->orderBy('employee_code')->pluck('employee_code')->all(),
            __('admin.labels.avg'),
            __('admin.labels.sum'),
            __('admin.options.unassigned'),
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function exportRows(): array
    {
        $employees = $this->reportEmployeesQuery()->orderBy('employee_code')->get();
        $keys = [
            'day',
            ...$employees->map(fn (Employee $employee): string => 'emp_'.$employee->id)->all(),
            'avg',
            'sum',
            'not_assigned',
        ];

        return $this->buildRows($employees)
            ->map(fn (array $row): array => array_map(fn (string $key): string => $row[$key], $keys))
            ->all();
    }

    protected function visibleEmployeesQuery(): Builder
    {
        return $this->scopeEmployees(Employee::query(), $this->currentEmployee());
    }

    protected function reportEmployeesQuery(): Builder
    {
        $filters = $this->filters ?? [];

        return $this->visibleEmployeesQuery()
            ->when($filters['department_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('department_id', $id))
            ->when($filters['team_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('team_id', $id))
            ->when(filled($filters['employee_ids'] ?? null), fn (Builder $q): Builder => $q->whereIn('id', $filters['employee_ids']));
    }

    /**
     * The calendar days the table lists. An empty "until" picker runs to today;
     * an empty "from" picker starts at the first matching paid order (or the
     * "until" day when there is none), so the "all" preset stays finite.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveDateRange(): array
    {
        [$from, $to] = $this->reportDateRange();

        $to = ($to ?? CarbonImmutable::today())->startOfDay();

        if ($from === null) {
            $earliestOrderAt = $this->filteredOrderQuery()->min('created_at');
            $from = $earliestOrderAt === null ? $to : CarbonImmutable::parse($earliestOrderAt)->startOfDay();
        }

        return [$from, $to];
    }

    /**
     * Paid orders, filtered exactly like the order line items report. An empty
     * date picker leaves that side of the range unbounded.
     */
    protected function filteredOrderQuery(): Builder
    {
        $filters = $this->filters ?? [];
        [$from, $to] = $this->reportDateRange();

        return Order::query()->filteredForReport(
            $from?->toDateString(),
            $to?->toDateString(),
            $filters['payment_method'] ?? null,
            $filters['source'] ?? null,
            OrderStatus::Paid->value,
        );
    }

    /**
     * One column per employee (labelled by employee code), plus trailing
     * Avg / Sum / Not Assigned columns. Each cell reads "orders (line items)".
     *
     * @param  Collection<int, Employee>  $employees
     * @return array<int, TextColumn>
     */
    protected function employeeColumns(Collection $employees): array
    {
        $columns = $employees
            ->map(fn (Employee $employee): TextColumn => TextColumn::make('emp_'.$employee->id)
                ->label($employee->employee_code)
                ->alignEnd()
                ->toggleable())
            ->all();

        $columns[] = TextColumn::make('avg')
            ->label(__('admin.labels.avg'))
            ->alignEnd()
            ->weight('bold');

        $columns[] = TextColumn::make('sum')
            ->label(__('admin.labels.sum'))
            ->alignEnd()
            ->weight('bold');

        $columns[] = TextColumn::make('not_assigned')
            ->label(__('admin.options.unassigned'))
            ->alignEnd()
            ->color('danger');

        return $columns;
    }

    /**
     * Daily [orders, line items] counts keyed by date then employee id
     * (or UNASSIGNED). Line items are counted like the order line items
     * report: one per order item, bundles expanded per selected course group.
     *
     * @return array<string, array<string, array{0: int, 1: int}>>
     */
    protected function dailyCounts(): array
    {
        $orders = $this->filteredOrderQuery()->get(['id', 'created_at', 'referred_by_employee_id']);

        $itemsByOrder = OrderItem::query()
            ->withCourseGroupRows()
            ->whereHas('order', fn (Builder $o): Builder => $o->whereIn('id', $orders->modelKeys()))
            ->selectRaw('order_items.order_id, count(*) as aggregate')
            ->groupBy('order_items.order_id')
            ->pluck('aggregate', 'order_id');

        $counts = [];

        foreach ($orders as $order) {
            $day = $order->created_at->toDateString();
            $key = $order->referred_by_employee_id ?? self::UNASSIGNED;

            $counts[$day][$key] ??= [0, 0];
            $counts[$day][$key][0]++;
            $counts[$day][$key][1] += (int) ($itemsByOrder->get($order->id) ?? 0);
        }

        return $counts;
    }

    protected function cell(int|float $orders, int|float $items): string
    {
        $format = fn (int|float $n): string => is_float($n) ? number_format($n, 1, '.', '') : (string) $n;

        return $format($orders).' ('.$format($items).')';
    }

    /**
     * @param  array{0: int, 1: int}  $pair
     * @return array{0: float, 1: float}
     */
    protected function divide(array $pair, int $by): array
    {
        return $by > 0 ? [round($pair[0] / $by, 1), round($pair[1] / $by, 1)] : [0.0, 0.0];
    }

    /**
     * Rows: one per calendar day in range, plus trailing Sum/Avg-per-employee
     * footer rows.
     *
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(Collection $employees): Collection
    {
        [$from, $to] = $this->resolveDateRange();
        $counts = $this->dailyCounts();

        $rows = collect();
        $employeeTotals = $employees->mapWithKeys(fn (Employee $employee): array => [$employee->id => [0, 0]])->all();
        $totalNotAssigned = [0, 0];
        $daysCount = 0;
        $employeeCount = max($employees->count(), 1);

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $daysCount++;
            $dateKey = $date->toDateString();
            $dayCounts = $counts[$dateKey] ?? [];

            $row = ['day' => $dateKey.' ('.$date->format('D').')'];
            $daySum = [0, 0];

            foreach ($employees as $employee) {
                [$orders, $items] = $dayCounts[$employee->id] ?? [0, 0];
                $row['emp_'.$employee->id] = $this->cell($orders, $items);
                $daySum[0] += $orders;
                $daySum[1] += $items;
                $employeeTotals[$employee->id][0] += $orders;
                $employeeTotals[$employee->id][1] += $items;
            }

            $notAssigned = $dayCounts[self::UNASSIGNED] ?? [0, 0];
            $totalNotAssigned[0] += $notAssigned[0];
            $totalNotAssigned[1] += $notAssigned[1];

            [$avgOrders, $avgItems] = $this->divide($daySum, $employeeCount);

            $row['sum'] = $this->cell($daySum[0], $daySum[1]);
            $row['avg'] = $this->cell($avgOrders, $avgItems);
            $row['not_assigned'] = $this->cell($notAssigned[0], $notAssigned[1]);

            $rows->push($row);
        }

        $sumRow = ['day' => __('admin.labels.sum_per_employee')];
        $avgRow = ['day' => __('admin.labels.avg_per_employee')];
        $grandTotal = [0, 0];

        foreach ($employees as $employee) {
            $total = $employeeTotals[$employee->id];
            $grandTotal[0] += $total[0];
            $grandTotal[1] += $total[1];

            $sumRow['emp_'.$employee->id] = $this->cell($total[0], $total[1]);
            $avgRow['emp_'.$employee->id] = $this->cell(...$this->divide($total, $daysCount));
        }

        $sumRow['sum'] = $this->cell($grandTotal[0], $grandTotal[1]);
        $sumRow['avg'] = $this->cell(...$this->divide($grandTotal, $employeeCount));
        $sumRow['not_assigned'] = $this->cell($totalNotAssigned[0], $totalNotAssigned[1]);

        $avgRow['sum'] = $this->cell(...$this->divide($grandTotal, $daysCount));
        $avgRow['avg'] = $this->cell(...$this->divide($grandTotal, $employeeCount * $daysCount));
        $avgRow['not_assigned'] = $this->cell(...$this->divide($totalNotAssigned, $daysCount));

        $rows->push($sumRow);
        $rows->push($avgRow);

        return $rows->values();
    }
}
