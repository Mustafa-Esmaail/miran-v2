<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Course;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\Team;
use App\Traits\HasServiceRequestReportFilters;
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

class EmployeeDailyRequestReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
        HasFiltersForm::updatedFilters as persistFiltersInSessionOnFilterUpdate;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.employee-daily-request-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.employee_daily_request_report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.request_reports');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('employee_daily_request_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.employee_daily_request_report');
    }

    /**
     * The employee columns depend on the department/team/employee_ids
     * filters, so the cached table (built once per request in
     * bootedInteractsWithTable(), before this request's filter update is
     * applied) must be rebuilt after the filters actually change.
     */
    public function updatedFilters(): void
    {
        $this->persistFiltersInSessionOnFilterUpdate();
        $this->employeesCache = null;
        $this->rowsCache = null;
        $this->table = $this->table($this->makeTable());
    }

    /**
     * The old week-to-date default has no matching preset, so the page opens
     * on the last seven days.
     */
    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Last7Days;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('source')
                    ->label(__('admin.labels.source'))
                    ->options(collect(ServiceRequestSource::cases())
                        ->mapWithKeys(fn (ServiceRequestSource $source): array => [$source->value => __('admin.options.'.$source->value)])
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('type_id')
                    ->label(__('admin.labels.type'))
                    ->options(fn (): array => ServiceRequestType::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestType $type): array => [$type->id => $type->getTranslatedName()])
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('status')
                    ->label(__('admin.labels.status'))
                    ->options(fn (): array => ServiceRequestStatus::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestStatus $status): array => [$status->slug => $status->getTranslatedName()])
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('course_id')
                    ->label(__('admin.labels.course'))
                    ->options(fn (): array => Course::query()
                        ->get()
                        ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())])
                        ->all())
                    ->searchable()
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
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('day')->label(__('admin.labels.date')),
                ...$this->employeeColumns($this->reportEmployees()),
            ]);
    }

    protected function exportName(): string
    {
        return 'employee-daily-request-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.date'),
            ...$this->reportEmployees()->map(fn (Employee $employee): string => (string) $employee->employee_code)->all(),
            __('admin.labels.avg'),
            __('admin.labels.sum'),
            __('admin.options.unassigned'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $employees = $this->reportEmployees();

        return $this->rows()
            ->map(fn (array $row): array => [
                $row['day'],
                ...$employees->map(fn (Employee $employee): int|float|null => $row['emp_'.$employee->id] ?? null)->all(),
                $row['avg'] ?? null,
                $row['sum'] ?? null,
                $row['not_assigned'] ?? null,
            ])
            ->all();
    }

    private ?Collection $employeesCache = null;

    private ?Collection $rowsCache = null;

    /**
     * @return Collection<int, Employee>
     */
    protected function reportEmployees(): Collection
    {
        return $this->employeesCache ??= $this->reportEmployeesQuery()->orderBy('employee_code')->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows($this->reportEmployees());
    }

    /**
     * Employee columns for this report: role-scoped visible employees,
     * narrowed further by department/team/employee_ids filters when set.
     */
    protected function reportEmployeesQuery(): Builder
    {
        $filters = $this->filters ?? [];

        return $this->visibleEmployeesQuery()
            ->when($filters['department_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('department_id', $id))
            ->when($filters['team_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('team_id', $id))
            ->when(filled($filters['employee_ids'] ?? null), fn (Builder $q): Builder => $q->whereIn('id', $filters['employee_ids']));
    }

    /**
     * The days the pivot lists. An open start falls back to the first
     * matching request (or the end day when there is none); an open end
     * falls back to today.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveDayRange(): array
    {
        [$from, $to] = $this->reportDateRange();

        $to = ($to ?? CarbonImmutable::today())->startOfDay();

        if ($from === null) {
            $firstRequestAt = $this->filteredDailyServiceRequestQuery()->min('created_at');
            $from = $firstRequestAt === null ? $to : CarbonImmutable::parse($firstRequestAt);
        }

        return [$from->startOfDay(), $to];
    }

    protected function filteredDailyServiceRequestQuery(): Builder
    {
        $filters = $this->filters ?? [];
        [$from, $to] = $this->reportDateRange();

        return $this->scopeLeads(ServiceRequest::query(), $this->currentEmployee())
            ->when($from, fn (Builder $q, CarbonImmutable $date): Builder => $q->whereDate('created_at', '>=', $date->toDateString()))
            ->when($to, fn (Builder $q, CarbonImmutable $date): Builder => $q->whereDate('created_at', '<=', $date->toDateString()))
            ->when($filters['source'] ?? null, fn (Builder $q, string $source): Builder => $q->where('source', $source))
            ->when($filters['type_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('type_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))
            ->when($filters['course_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('course_id', $id));
    }

    /**
     * One numeric column per employee (labelled by employee code), plus
     * trailing Avg / Sum / Not Assigned columns.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array<int, TextColumn>
     */
    protected function employeeColumns(Collection $employees): array
    {
        $columns = $employees
            ->map(fn (Employee $employee): TextColumn => TextColumn::make('emp_'.$employee->id)
                ->label($employee->employee_code)
                ->numeric()
                ->alignEnd()
                ->toggleable())
            ->all();

        $columns[] = TextColumn::make('avg')
            ->label(__('admin.labels.avg'))
            ->numeric(decimalPlaces: 1)
            ->alignEnd()
            ->weight('bold');

        $columns[] = TextColumn::make('sum')
            ->label(__('admin.labels.sum'))
            ->numeric()
            ->alignEnd()
            ->weight('bold');

        $columns[] = TextColumn::make('not_assigned')
            ->label(__('admin.options.unassigned'))
            ->numeric()
            ->alignEnd()
            ->color('danger');

        return $columns;
    }

    /**
     * Rows: one per calendar day in range (counts per employee, day sum/avg,
     * unassigned count), plus trailing Sum/Avg-per-employee footer rows.
     *
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(Collection $employees): Collection
    {
        [$from, $to] = $this->resolveDayRange();
        $baseQuery = $this->filteredDailyServiceRequestQuery();

        $countsByDay = (clone $baseQuery)
            ->whereNotNull('assigned_to')
            ->selectRaw('DATE(created_at) as day, assigned_to, count(*) as aggregate')
            ->groupBy('day', 'assigned_to')
            ->get()
            ->groupBy('day');

        $notAssignedByDay = (clone $baseQuery)
            ->whereNull('assigned_to')
            ->selectRaw('DATE(created_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $rows = collect();
        $employeeTotals = $employees->mapWithKeys(fn (Employee $employee): array => [$employee->id => 0]);
        $totalNotAssigned = 0;
        $daysCount = 0;
        $employeeCount = max($employees->count(), 1);

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $daysCount++;
            $dateKey = $date->toDateString();

            $dayCounts = ($countsByDay->get($dateKey) ?? collect())->pluck('aggregate', 'assigned_to');

            $row = ['day' => $dateKey.' ('.$date->format('D').')'];
            $daySum = 0;

            foreach ($employees as $employee) {
                $count = (int) ($dayCounts->get($employee->user_id) ?? 0);
                $row['emp_'.$employee->id] = $count;
                $daySum += $count;
                $employeeTotals[$employee->id] += $count;
            }

            $notAssigned = (int) ($notAssignedByDay->get($dateKey) ?? 0);
            $totalNotAssigned += $notAssigned;

            $row['sum'] = $daySum;
            $row['avg'] = round($daySum / $employeeCount, 1);
            $row['not_assigned'] = $notAssigned;

            $rows->push($row);
        }

        $sumRow = ['day' => __('admin.labels.sum_per_employee')];
        $avgRow = ['day' => __('admin.labels.avg_per_employee')];

        foreach ($employees as $employee) {
            $total = $employeeTotals[$employee->id];
            $sumRow['emp_'.$employee->id] = $total;
            $avgRow['emp_'.$employee->id] = $daysCount > 0 ? round($total / $daysCount, 1) : 0;
        }

        $grandTotal = array_sum($employeeTotals->all());

        $sumRow['sum'] = $grandTotal;
        $sumRow['avg'] = round($grandTotal / $employeeCount, 1);
        $sumRow['not_assigned'] = $totalNotAssigned;

        $avgRow['sum'] = $daysCount > 0 ? round($grandTotal / $daysCount, 1) : 0;
        $avgRow['avg'] = $daysCount > 0 ? round(($grandTotal / $employeeCount) / $daysCount, 1) : 0;
        $avgRow['not_assigned'] = $daysCount > 0 ? round($totalNotAssigned / $daysCount, 1) : 0;

        $rows->push($sumRow);
        $rows->push($avgRow);

        return $rows->values();
    }
}
