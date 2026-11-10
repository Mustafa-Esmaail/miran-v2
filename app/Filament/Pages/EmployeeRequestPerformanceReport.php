<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Employee;
use App\Models\ServiceRequestStatus;
use App\Traits\HasServiceRequestReportFilters;
use Filament\Forms\Components\Field;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class EmployeeRequestPerformanceReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 14;

    protected string $view = 'filament.pages.employee-request-performance-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.employee_request_performance_report');
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
        return auth('admin')->user()?->can('employee_request_performance_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.employee_request_performance_report');
    }

    /**
     * The date pickers used to start empty (no bound), which the "all"
     * preset reproduces exactly.
     */
    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::All;
    }

    public function filtersForm(Schema $schema): Schema
    {
        $otherFilters = array_values(array_filter(
            $this->serviceRequestReportFilterComponents(),
            fn (Field $component): bool => ! in_array($component->getName(), ['date_from', 'date_to'], true),
        ));

        return $schema
            ->columns(4)
            ->components([
                ...$this->dateRangeFields(),
                ...$otherFilters,
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('employee')->label(__('admin.labels.employee')),
                TextColumn::make('role')->label(__('admin.labels.role')),
                ...$this->statusCountColumns(),
            ]);
    }

    protected function exportName(): string
    {
        return 'employee-request-performance-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.employee'),
            __('admin.labels.role'),
            ...$this->reportStatuses()->map(fn (ServiceRequestStatus $status): string => (string) $status->getTranslatedName())->all(),
            __('admin.labels.total'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $statuses = $this->reportStatuses();

        return $this->rows()
            ->map(fn (array $row): array => [
                $row['employee'],
                $row['role'],
                ...$statuses->map(fn (ServiceRequestStatus $status): int => (int) ($row[$status->slug] ?? 0))->all(),
                $row['total'],
            ])
            ->all();
    }

    private ?Collection $rowsCache = null;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows();
    }

    /**
     * The active statuses in the same order as the status columns.
     *
     * @return Collection<int, ServiceRequestStatus>
     */
    protected function reportStatuses(): Collection
    {
        return ServiceRequestStatus::query()->active()->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(): Collection
    {
        $employees = $this->visibleEmployeesQuery()->get();

        if ($employeeFilter = $this->filters['employee_id'] ?? null) {
            $employees = $employees->where('user_id', $employeeFilter)->values();
        }

        $baseQuery = $this->filteredServiceRequestQuery();

        return $employees
            ->map(function (Employee $employee) use ($baseQuery): array {
                $counts = (clone $baseQuery)
                    ->where('assigned_to', $employee->user_id)
                    ->selectRaw('status, count(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status');

                return array_merge(
                    [
                        'employee' => $employee->name,
                        'role' => $employee->role ? __('admin.options.'.$employee->role->value) : '—',
                    ],
                    $this->pivotStatusCounts($counts)
                );
            })
            ->sortByDesc('total')
            ->values();
    }
}
