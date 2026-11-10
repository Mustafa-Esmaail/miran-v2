<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LmsEnrollmentReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
        HasFiltersForm::updatedFilters as persistFiltersInSessionOnFilterUpdate;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?int $navigationSort = 18;

    protected string $view = 'filament.pages.lms-enrollment-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.lms_enrollment_report');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.reports');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('lms_enrollment_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.lms_enrollment_report');
    }

    /**
     * The date columns depend on the date_from/date_to filters and the group
     * rows depend on course_id/course_group_id, so the cached table (built
     * once per request, before this request's filter update is applied)
     * must be rebuilt after filters change.
     */
    public function updatedFilters(): void
    {
        $this->persistFiltersInSessionOnFilterUpdate();
        $this->rowsCache = null;
        $this->table = $this->table($this->makeTable());
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('course_id')
                    ->label(__('admin.labels.course'))
                    ->options(fn (): array => Course::query()
                        ->get()
                        ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())])
                        ->all())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null))
                    ->placeholder(__('admin.labels.all')),
                Select::make('course_group_id')
                    ->label(__('admin.labels.group'))
                    ->options(function (Get $get): array {
                        $courseId = $get('course_id');

                        if (! $courseId) {
                            return [];
                        }

                        return CourseGroup::query()
                            ->where('course_id', $courseId)
                            ->get()
                            ->mapWithKeys(fn (CourseGroup $group): array => [$group->id => $group->getTranslation('title', app()->getLocale())])
                            ->all();
                    })
                    ->searchable()
                    ->placeholder(__('admin.labels.all')),
            ]);
    }

    public function table(Table $table): Table
    {
        [$from, $to] = $this->resolveDateRange();

        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('group')->label(__('admin.labels.group')),
                ...$this->dayColumns($from, $to),
            ]);
    }

    /**
     * Course groups in scope for the current course_id/course_group_id
     * filters — every group when neither filter is set.
     */
    protected function groupsInScope(): Collection
    {
        $filters = $this->filters ?? [];

        return CourseGroup::query()
            ->with('course')
            ->when($filters['course_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('course_id', $id))
            ->when($filters['course_group_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('id', $id))
            ->get();
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function dayColumns(Carbon $from, Carbon $to): array
    {
        $columns = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $columns[] = TextColumn::make('day_'.$date->format('Y-m-d'))
                ->label($date->format('Y-m-d'))
                ->numeric()
                ->alignEnd()
                ->toggleable();
        }

        return $columns;
    }

    /**
     * The day columns to show. An empty "until" means today; an empty "from"
     * starts at the earliest enrollment in scope (or the "until" day when
     * there is none), so the "all" preset still yields a finite range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(): array
    {
        [$from, $to] = $this->reportDateRange();

        $resolvedTo = $to !== null ? Carbon::parse($to->toDateString()) : today();

        if ($from !== null) {
            return [Carbon::parse($from->toDateString()), $resolvedTo];
        }

        $earliestEnrollment = Enrollment::query()
            ->whereIn('course_group_id', $this->groupsInScope()->pluck('id'))
            ->min('created_at');

        $resolvedFrom = $earliestEnrollment !== null
            ? Carbon::parse($earliestEnrollment)->startOfDay()
            : $resolvedTo->copy();

        return [$resolvedFrom, $resolvedTo];
    }

    protected function exportName(): string
    {
        return 'lms-enrollment-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        [$from, $to] = $this->resolveDateRange();

        $headings = [__('admin.labels.group')];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $headings[] = $date->format('Y-m-d');
        }

        return $headings;
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        return $this->rows()
            ->map(fn (array $row): array => array_values($row))
            ->all();
    }

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $rowsCache = null;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows();
    }

    /**
     * Rows: one per course group in scope, with an enrollment count per
     * date column.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(): Collection
    {
        [$from, $to] = $this->resolveDateRange();
        $groups = $this->groupsInScope();
        $groupIds = $groups->pluck('id');

        $countsByGroup = Enrollment::query()
            ->whereIn('course_group_id', $groupIds)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw('course_group_id, DATE(created_at) as day, count(*) as aggregate')
            ->groupBy('course_group_id', 'day')
            ->get()
            ->groupBy('course_group_id');

        $rows = collect();

        foreach ($groups as $group) {
            $groupCounts = ($countsByGroup->get($group->id) ?? collect())->pluck('aggregate', 'day');

            $row = [
                'group' => ($group->course?->getTranslation('title', app()->getLocale()) ?? '—').' — '.$group->getTranslation('title', app()->getLocale()),
            ];

            foreach (CarbonPeriod::create($from, $to) as $date) {
                $row['day_'.$date->format('Y-m-d')] = (int) ($groupCounts->get($date->toDateString()) ?? 0);
            }

            $rows->push($row);
        }

        return $rows;
    }
}
