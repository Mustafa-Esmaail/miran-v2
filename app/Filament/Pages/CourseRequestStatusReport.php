<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\ServiceRequestStatus;
use App\Traits\HasServiceRequestReportFilters;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CourseRequestStatusReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.course-request-status-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_request_status_report');
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
        return auth('admin')->user()?->can('course_request_status_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.course_request_status_report');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                ...$this->dateRangeFields(),
                ...$this->filterComponentsWithoutDates(),
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
                TextColumn::make('title')->label(__('admin.labels.item')),
                ...$this->statusCountColumns(),
            ]);
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::All;
    }

    protected function exportName(): string
    {
        return 'course-request-status-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.type'),
            __('admin.labels.item'),
            ...$this->exportStatuses()->map(fn (ServiceRequestStatus $status): string => $status->getTranslatedName())->all(),
            __('admin.labels.total'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        $statuses = $this->exportStatuses();

        return $this->rows()
            ->map(fn (array $row): array => [
                __('admin.options.'.$row['type']),
                $row['title'],
                ...$statuses->map(fn (ServiceRequestStatus $status): int => $row[$status->slug] ?? 0)->all(),
                $row['total'],
            ])
            ->all();
    }

    /**
     * The same active statuses, in the same order, as the table's status columns.
     *
     * @return Collection<int, ServiceRequestStatus>
     */
    private function exportStatuses(): Collection
    {
        return ServiceRequestStatus::query()->active()->orderBy('sort_order')->get();
    }

    /**
     * The shared service-request filters minus their own date pickers, which
     * the preset-driven date range fields replace.
     *
     * @return array<int, Component>
     */
    private function filterComponentsWithoutDates(): array
    {
        return array_values(array_filter(
            $this->serviceRequestReportFilterComponents(),
            fn (Component $component): bool => ! $component instanceof DatePicker,
        ));
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
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildRows(): Collection
    {
        $courseIdFilter = $this->filters['course_id'] ?? null;
        $bundleIdFilter = $this->filters['bundle_id'] ?? null;

        $baseQuery = $this->filteredServiceRequestQuery();

        $courseIds = $courseIdFilter
            ? collect([$courseIdFilter])
            : (clone $baseQuery)->whereNotNull('course_id')->distinct()->pluck('course_id');

        $bundleIds = $bundleIdFilter
            ? collect([$bundleIdFilter])
            : (clone $baseQuery)->whereNotNull('bundle_id')->distinct()->pluck('bundle_id');

        $courseTitles = Course::query()->whereIn('id', $courseIds)->get()
            ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())]);

        $bundleTitles = Bundle::query()->whereIn('id', $bundleIds)->get()
            ->mapWithKeys(fn (Bundle $bundle): array => [$bundle->id => $bundle->getTranslation('title', app()->getLocale())]);

        $rows = collect();

        foreach ($courseIds as $courseId) {
            $counts = (clone $baseQuery)
                ->where('course_id', $courseId)
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $rows->push(array_merge(
                ['type' => 'course', 'title' => $courseTitles->get($courseId) ?? '—'],
                $this->pivotStatusCounts($counts)
            ));
        }

        foreach ($bundleIds as $bundleId) {
            $counts = (clone $baseQuery)
                ->where('bundle_id', $bundleId)
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $rows->push(array_merge(
                ['type' => 'bundle', 'title' => $bundleTitles->get($bundleId) ?? '—'],
                $this->pivotStatusCounts($counts)
            ));
        }

        return $rows->sortByDesc('total')->values();
    }
}
