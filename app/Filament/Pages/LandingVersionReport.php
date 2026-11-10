<?php

namespace App\Filament\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use App\Filament\Pages\Concerns\HasReportExports;
use App\Models\Course;
use App\Models\Page as LandingPage;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * One row per landing page version: when it was live, its course and
 * marketing source, where to preview it, the URL that replaced it and how many
 * B2C leads it received.
 */
class LandingVersionReport extends Page implements HasTable
{
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }
    use HasReportExports;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.landing-version-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.landing_version_report');
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
        return auth('admin')->user()?->can('landing_version_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.landing_version_report');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('course_id')
                    ->label(__('admin.labels.course'))
                    ->options(fn (): array => Course::query()
                        ->whereIn('id', LandingPage::query()
                            ->whereIn('template', LandingPage::VERSIONED_TEMPLATES)
                            ->where('landable_type', Course::class)
                            ->select('landable_id'))
                        ->get()
                        ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())])
                        ->all())
                    ->searchable(),
                Select::make('landing_key')
                    ->label(__('admin.landing_versions.labels.landing_key'))
                    ->options(fn (): array => LandingPage::query()
                        ->whereIn('template', LandingPage::VERSIONED_TEMPLATES)
                        ->whereNotNull('landing_key')
                        ->distinct()
                        ->orderBy('landing_key')
                        ->pluck('landing_key', 'landing_key')
                        ->all())
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->rows())
            ->columns([
                TextColumn::make('period')
                    ->label(__('admin.landing_versions.labels.period')),
                TextColumn::make('version_name')
                    ->label(__('admin.landing_versions.labels.version_name'))
                    ->badge()
                    ->color(fn (array $record): string => $record['is_live'] ? 'success' : 'gray'),
                TextColumn::make('course_title')
                    ->label(__('admin.labels.course'))
                    ->placeholder('—'),
                TextColumn::make('marketing_source')
                    ->label(__('admin.landing_versions.labels.marketing_source'))
                    ->placeholder('—'),
                TextColumn::make('preview_url')
                    ->label(__('admin.landing_versions.labels.preview_url'))
                    ->url(fn (array $record): string => $record['preview_url'], shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('landing_url')
                    ->label(__('admin.landing_versions.labels.landing_url'))
                    ->url(fn (array $record): string => $record['landing_url'], shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('leads_count')
                    ->label(__('admin.landing_versions.labels.leads_count'))
                    ->numeric()
                    ->alignEnd(),
            ]);
    }

    /**
     * @return array{versions_count: int, leads_total: int}
     */
    public function getReportTotals(): array
    {
        $rows = $this->rows();

        return [
            'versions_count' => $rows->count(),
            'leads_total' => (int) $rows->sum('leads_count'),
        ];
    }

    protected function exportName(): string
    {
        return 'landing-version-report';
    }

    /**
     * @return array<int, string>
     */
    protected function exportHeadings(): array
    {
        return [
            __('admin.landing_versions.labels.published_at'),
            __('admin.landing_versions.labels.drafted_at'),
            __('admin.landing_versions.labels.version_name'),
            __('admin.labels.course'),
            __('admin.landing_versions.labels.marketing_source'),
            __('admin.landing_versions.labels.preview_url'),
            __('admin.landing_versions.labels.landing_url'),
            __('admin.landing_versions.labels.leads_count'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    protected function exportRows(): array
    {
        return $this->rows()
            ->map(fn (array $row): array => [
                $row['published_at'],
                $row['drafted_at'] ?? __('admin.landing_versions.labels.live'),
                $row['version_name'],
                $row['course_title'],
                $row['marketing_source'],
                $row['preview_url'],
                $row['landing_url'],
                $row['leads_count'],
            ])
            ->push([
                __('admin.labels.total'), null, null, null, null, null, null,
                $this->getReportTotals()['leads_total'],
            ])
            ->all();
    }

    private ?Collection $rowsCache = null;

    protected function rows(): Collection
    {
        return $this->rowsCache ??= $this->buildRows();
    }

    /**
     * @return Collection<int, array{key: string, period: string, published_at: ?string, drafted_at: ?string, version_name: string, is_live: bool, course_title: ?string, marketing_source: ?string, preview_url: string, landing_url: string, leads_count: int}>
     */
    protected function buildRows(): Collection
    {
        $courseId = $this->filters['course_id'] ?? null;
        $landingKey = $this->filters['landing_key'] ?? null;
        $locale = app()->getLocale();

        return LandingPage::query()
            ->whereIn('template', LandingPage::VERSIONED_TEMPLATES)
            ->whereNotNull('landing_key')
            ->when($courseId, fn (Builder $q, int|string $id): Builder => $q
                ->where('landable_type', Course::class)
                ->where('landable_id', $id))
            ->when($landingKey, fn (Builder $q, string $key): Builder => $q->where('landing_key', $key))
            ->with('landable')
            ->withCount(['serviceRequests as leads_count' => fn (Builder $q): Builder => $q
                ->whereNotIn('source', B2cServiceRequestResource::EXCLUDED_SOURCES)])
            ->orderBy('landing_key')
            ->orderByDesc('version')
            ->get()
            ->map(function (LandingPage $page) use ($locale): array {
                $publishedAt = $page->published_at?->format('Y-m-d');
                $draftedAt = $page->drafted_at?->format('Y-m-d');

                return [
                    'key' => (string) $page->id,
                    'period' => ($publishedAt ?? '—').' → '.($draftedAt ?? __('admin.landing_versions.labels.live')),
                    'published_at' => $publishedAt,
                    'drafted_at' => $draftedAt,
                    'version_name' => $page->versionLabel(),
                    'is_live' => $page->isLiveVersion(),
                    'course_title' => $page->landable?->getTranslation('title', $locale),
                    'marketing_source' => $page->marketing_source,
                    // Live versions are public; drafted ones open through the admin preview at their -vN slug.
                    'preview_url' => $page->isLiveVersion()
                        ? url('/'.$page->slug)
                        : route('admin.pages.preview', $page),
                    'landing_url' => url('/'.$page->landing_key),
                    'leads_count' => (int) $page->leads_count,
                ];
            })
            ->values();
    }
}
