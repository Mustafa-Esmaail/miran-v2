<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\DealStage;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Filament\Widgets\B2bDealStatsWidget;
use App\Filament\Widgets\B2bRequestStatsWidget;
use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use App\Traits\HasServiceRequestReportFilters;
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

class B2bRequestsReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 18;

    protected string $view = 'filament.pages.b2b-requests-report';

    /**
     * Name of the team the Team filter defaults to (the corporate-training team).
     */
    public const DEFAULT_TEAM_NAME = 'الشركات';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.b2b_requests_report');
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
        return auth('admin')->user()?->can('b2b_requests_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.b2b_requests_report');
    }

    public function getHeaderWidgets(): array
    {
        return [
            B2bRequestStatsWidget::class,
            B2bDealStatsWidget::class,
        ];
    }

    /**
     * Restricts a ServiceRequest query to B2B (corporate training) requests,
     * excluding anything sitting on a spam status. When a company is selected,
     * only requests with at least one deal for that company are kept.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function scopeToB2b(Builder $query, array $filters = []): Builder
    {
        return $query
            ->where('source', ServiceRequestSource::CorporateTraining)
            ->whereDoesntHave('statusModel', fn (Builder $q): Builder => $q->where('is_spam', true))
            ->when($filters['company_id'] ?? null, fn (Builder $q, int|string $companyId): Builder => $q
                ->whereHas('deals', fn (Builder $deals): Builder => $deals->where('company_id', $companyId)));
    }

    /**
     * Deals linked to the given (already filtered) B2B requests, narrowed to
     * the selected company when one is set.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function dealsForRequests(Builder $requestQuery, array $filters = []): Builder
    {
        return Deal::query()
            ->whereIn('service_request_id', (clone $requestQuery)->select('service_requests.id'))
            ->when($filters['company_id'] ?? null, fn (Builder $q, int|string $companyId): Builder => $q->where('company_id', $companyId));
    }

    /**
     * Quotes belonging to the given deals.
     */
    public static function quotesForDeals(Builder $dealQuery): Builder
    {
        return Quote::query()->whereIn('deal_id', (clone $dealQuery)->select('deals.id'));
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
                    ->default(fn (): ?int => Team::query()->where('name', self::DEFAULT_TEAM_NAME)->value('id'))
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
                    ->options(fn (): array => $this->b2bStatusOptions())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
                Select::make('company_id')
                    ->label(__('admin.labels.company'))
                    ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
            ]);
    }

    /**
     * Status filter options scoped to the Requests (Tlbat) type — the one
     * B2B requests move through — plus the shared unassigned statuses.
     *
     * @return array<string, string>
     */
    protected function b2bStatusOptions(): array
    {
        return ServiceRequestStatus::query()
            ->active()
            ->where('is_spam', false)
            ->orderBy('sort_order')
            ->where(function (Builder $query): void {
                $query->whereNull('stage_id')
                    ->orWhereHas('stage.type', fn (Builder $q) => $q->where('slug', 'tlbat'));
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
                TextColumn::make('activities')
                    ->label(__('admin.dashboard_widgets.stats.b2b_activities'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('total')
                    ->label(__('admin.labels.total'))
                    ->numeric()
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('succeeded')
                    ->label(__('admin.dashboard_widgets.stats.b2b_succeeded'))
                    ->numeric()
                    ->alignEnd()
                    ->badge()
                    ->color('success'),
                TextColumn::make('failed')
                    ->label(__('admin.dashboard_widgets.stats.b2b_cancelled'))
                    ->numeric()
                    ->alignEnd()
                    ->badge()
                    ->color('danger'),
                TextColumn::make('open')
                    ->label(__('admin.dashboard_widgets.stats.b2b_open'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('success_rate')
                    ->label(__('admin.dashboard_widgets.stats.b2b_success_rate'))
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '—' : $state.'%')
                    ->alignEnd(),
                TextColumn::make('deals')
                    ->label(__('admin.dashboard_widgets.stats.b2b_deals'))
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('deals_won')
                    ->label(__('admin.dashboard_widgets.stats.b2b_deals_won'))
                    ->numeric()
                    ->alignEnd()
                    ->badge()
                    ->color('success'),
                TextColumn::make('quotes')
                    ->label(__('admin.dashboard_widgets.stats.b2b_quotes'))
                    ->numeric()
                    ->alignEnd(),
            ]);
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::All;
    }

    protected function exportName(): string
    {
        return 'b2b-requests-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.employee'),
            __('admin.labels.role'),
            __('admin.dashboard_widgets.stats.b2b_activities'),
            __('admin.labels.total'),
            __('admin.dashboard_widgets.stats.b2b_succeeded'),
            __('admin.dashboard_widgets.stats.b2b_cancelled'),
            __('admin.dashboard_widgets.stats.b2b_open'),
            __('admin.dashboard_widgets.stats.b2b_success_rate').' (%)',
            __('admin.dashboard_widgets.stats.b2b_deals'),
            __('admin.dashboard_widgets.stats.b2b_deals_won'),
            __('admin.dashboard_widgets.stats.b2b_quotes'),
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
                $row['activities'],
                $row['total'],
                $row['succeeded'],
                $row['failed'],
                $row['open'],
                $row['success_rate'],
                $row['deals'],
                $row['deals_won'],
                $row['quotes'],
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
     * Base ServiceRequest query for this report: role-scoped, filter-scoped,
     * and restricted to non-spam B2B requests.
     */
    protected function b2bQuery(): Builder
    {
        return static::scopeToB2b($this->filteredServiceRequestQuery(), $this->filters ?? []);
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

        $baseQuery = $this->b2bQuery();

        return $employees
            ->map(fn (Employee $employee): array => $this->buildRowForEmployee($employee, $baseQuery))
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Deals are credited to their own owner (deals.employee_id), not to the
     * request's assignee.
     *
     * @return array{employee: string, role: string, activities: int, total: int, succeeded: int, failed: int, open: int, success_rate: float|null, deals: int, deals_won: int, quotes: int}
     */
    protected function buildRowForEmployee(Employee $employee, Builder $baseQuery): array
    {
        $agentQuery = fn (): Builder => (clone $baseQuery)->where('assigned_to', $employee->user_id);

        $total = ($agentQuery)()->count();
        $succeeded = ($agentQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'won'))->count();
        $failed = ($agentQuery)()->whereHas('statusModel', fn (Builder $q) => $q->where('outcome', 'lost'))->count();
        $closed = $succeeded + $failed;

        $employeeDeals = fn (): Builder => static::dealsForRequests($baseQuery, $this->filters ?? [])
            ->where('employee_id', $employee->id);

        return [
            'employee' => $employee->name,
            'role' => $employee->role ? __('admin.options.'.$employee->role->value) : '—',
            'activities' => $this->countActivitiesFor($employee, $baseQuery),
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'open' => $total - $closed,
            'success_rate' => $closed > 0 ? round($succeeded / $closed * 100, 1) : null,
            'deals' => ($employeeDeals)()->count(),
            'deals_won' => ($employeeDeals)()->where('stage', DealStage::ClosedWon)->count(),
            'quotes' => static::quotesForDeals(($employeeDeals)())->count(),
        ];
    }

    /**
     * Every action the employee performed on B2B requests: request history
     * (status changes, notes, reassignments) plus logged CRM activities
     * (calls, meetings, visits, WhatsApp, emails).
     */
    protected function countActivitiesFor(Employee $employee, Builder $baseQuery): int
    {
        $historyCount = ServiceRequestActivity::query()
            ->where('actor_user_id', $employee->user_id)
            ->whereIn('service_request_id', (clone $baseQuery)->select('service_requests.id'))
            ->count();

        $crmCount = CrmActivity::query()
            ->where('employee_id', $employee->id)
            ->where('entity_type', (new ServiceRequest)->getMorphClass())
            ->whereIn('entity_id', (clone $baseQuery)->select('service_requests.id'))
            ->count();

        return $historyCount + $crmCount;
    }
}
