<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Filament\Widgets\ComplaintRedFlagStatsWidget;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use App\Traits\HasServiceRequestReportFilters;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ComplaintOpsReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.complaint-ops-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.complaint_ops_report');
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
        return auth('admin')->user()?->can('complaint_ops_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.complaint_ops_report');
    }

    public function getHeaderWidgets(): array
    {
        return [
            ComplaintRedFlagStatsWidget::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('department_id')
                    ->label(__('admin.labels.department'))
                    ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => Department::query()->where('code', 'DEPT-OPS')->value('id'))
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
                    ->label(__('admin.labels.employee'))
                    ->options(fn (): array => $this->visibleEmployeesQuery()
                        ->get()
                        ->pluck('name', 'user_id')
                        ->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('status')
                    ->label(__('admin.labels.status'))
                    ->options(fn (): array => $this->complaintStatusOptions())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
            ]);
    }

    /**
     * Status filter options scoped to the Complaint (Shakawi) type only —
     * its own stage-linked statuses, plus the shared unassigned/legacy ones
     * (e.g. 'new') that a complaint can still be sitting on.
     *
     * @return array<string, string>
     */
    protected function complaintStatusOptions(): array
    {
        return ServiceRequestStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->where(function (Builder $query): void {
                $query->whereNull('stage_id')
                    ->orWhereHas('stage.type', fn (Builder $q) => $q->where('slug', 'shakawi'));
            })
            ->get()
            ->mapWithKeys(fn (ServiceRequestStatus $status): array => [$status->slug => $status->getTranslatedName()])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('employee')->label(__('admin.labels.employee')),
                TextColumn::make('role')->label(__('admin.labels.role')),
                TextColumn::make('total')
                    ->label(__('admin.labels.total'))
                    ->numeric()
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('resolved')
                    ->label(__('admin.dashboard_widgets.stats.complaint_resolved'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('avg_resolution_hours')
                    ->label(__('admin.labels.avg_resolution_time'))
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '—' : __('admin.labels.hours_short', ['hours' => $state]))
                    ->alignEnd(),
                TextColumn::make('late')
                    ->label(__('admin.dashboard_widgets.stats.complaint_late'))
                    ->numeric()
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? Color::hex($this->statusColor('complaint_late')) : 'gray'),
                TextColumn::make('not_contacted')
                    ->label(__('admin.dashboard_widgets.stats.complaint_not_contacted'))
                    ->numeric()
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? Color::hex($this->statusColor('complaint_not_contacted')) : 'gray'),
            ]);
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::All;
    }

    protected function exportName(): string
    {
        return 'complaint-ops-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.employee'),
            __('admin.labels.role'),
            __('admin.labels.total'),
            __('admin.dashboard_widgets.stats.complaint_resolved'),
            __('admin.labels.avg_resolution_time'),
            __('admin.dashboard_widgets.stats.complaint_late'),
            __('admin.dashboard_widgets.stats.complaint_not_contacted'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        return $this->rows()
            ->map(fn (array $row): array => [
                $row['employee'],
                $row['role'],
                $row['total'],
                $row['resolved'],
                $row['avg_resolution_hours'],
                $row['late'],
                $row['not_contacted'],
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
     * The actual hex color stored on a status, e.g. to color a badge exactly
     * like that status is colored everywhere else (Kanban board, table).
     */
    protected function statusColor(string $slug): string
    {
        return ServiceRequestStatus::query()->where('slug', $slug)->value('color') ?? '#6b7280';
    }

    /**
     * Base ServiceRequest query for this report: role-scoped, filter-scoped,
     * and restricted to the Complaint source.
     */
    protected function complaintQuery(): Builder
    {
        return $this->filteredServiceRequestQuery()->where('source', ServiceRequestSource::Complaint);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(): Collection
    {
        $filters = $this->filters ?? [];

        $employees = $this->visibleEmployeesQuery()
            ->when($filters['department_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('department_id', $id))
            ->when($filters['team_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('team_id', $id))
            ->get();

        if ($employeeFilter = $filters['employee_id'] ?? null) {
            $employees = $employees->where('user_id', $employeeFilter)->values();
        }

        $baseQuery = $this->complaintQuery();

        return $employees
            ->map(fn (Employee $employee): array => $this->buildRowForEmployee($employee, $baseQuery))
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRowForEmployee(Employee $employee, Builder $baseQuery): array
    {
        $agentQuery = fn (): Builder => (clone $baseQuery)->where('assigned_to', $employee->user_id);

        $resolvedRequests = ($agentQuery)()
            ->whereHas('statusModel', fn (Builder $q) => $q->whereIn('outcome', ['won', 'lost']))
            ->get(['created_at', 'updated_at']);

        $resolvedCount = $resolvedRequests->count();

        $avgResolutionHours = $resolvedCount > 0
            ? round($resolvedRequests->avg(
                fn (ServiceRequest $request): float => $request->created_at->diffInHours($request->updated_at, absolute: true)
            ), 1)
            : null;

        return [
            'employee' => $employee->name,
            'role' => $employee->role ? __('admin.options.'.$employee->role->value) : '—',
            'total' => ($agentQuery)()->count(),
            'resolved' => $resolvedCount,
            'avg_resolution_hours' => $avgResolutionHours,
            'late' => ($agentQuery)()->where('status', 'complaint_late')->count(),
            'not_contacted' => ($agentQuery)()->where('status', 'complaint_not_contacted')->count(),
        ];
    }
}
