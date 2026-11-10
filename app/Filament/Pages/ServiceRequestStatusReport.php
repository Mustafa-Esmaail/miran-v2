<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Pages\Concerns\HasReportExports;
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

class ServiceRequestStatusReport extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, HasServiceRequestReportFilters, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 16;

    protected string $view = 'filament.pages.service-request-status-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.service_request_status_report');
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
        return auth('admin')->user()?->can('service_request_status_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.service_request_status_report');
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
                TextColumn::make('source')
                    ->label(__('admin.labels.source')),
                ...$this->statusCountColumns(),
            ]);
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::All;
    }

    protected function exportName(): string
    {
        return 'service-request-status-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.labels.source'),
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
                $row['source'],
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
        $baseQuery = $this->filteredServiceRequestQuery();

        $rows = collect(ServiceRequestSource::cases())
            ->map(function (ServiceRequestSource $source) use ($baseQuery): array {
                $counts = (clone $baseQuery)
                    ->where('source', $source->value)
                    ->selectRaw('status, count(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status');

                return array_merge(
                    ['source' => __('admin.options.'.$source->value)],
                    $this->pivotStatusCounts($counts)
                );
            });

        $totalCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $rows->push(array_merge(
            ['source' => __('admin.labels.total')],
            $this->pivotStatusCounts($totalCounts)
        ));

        return $rows->values();
    }
}
