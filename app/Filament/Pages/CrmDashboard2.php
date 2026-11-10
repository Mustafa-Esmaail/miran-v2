<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Widgets\KpiDailySalesChartWidget;
use App\Filament\Widgets\KpiMonthlyTrendChartWidget;
use App\Filament\Widgets\KpiPaymentMethodsChartWidget;
use App\Filament\Widgets\KpiStatsWidget;
use App\Filament\Widgets\KpiTargetPaceWidget;
use App\Filament\Widgets\KpiTopCoursesWidget;
use App\Filament\Widgets\KpiTopSellersChartWidget;
use App\Services\KpiDashboardService;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class CrmDashboard2 extends Page
{
    use HasDateRangePresets;
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'kpi-dashboard';

    protected string $view = 'filament.pages.crm-dashboard-2';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.kpi_dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('crm_dashboard.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.kpi_dashboard');
    }

    public function getSubheading(): ?string
    {
        $service = app(KpiDashboardService::class);
        [$from, $to] = $service->range($this->filters ?? []);

        return __('admin.kpi_dashboard.subheading', [
            'from' => $from->format('d-m-Y'),
            'to' => $to->format('d-m-Y'),
            'scope' => $service->scopeLabel(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function getPageClasses(): array
    {
        return ['fi-kpi-dashboard'];
    }

    /**
     * "All time" and single days are left out: the page is built around a
     * target pace over a working period.
     *
     * @return array<int, DateRangePreset>
     */
    protected function availableDateRangePresets(): array
    {
        return [
            DateRangePreset::ThisMonth,
            DateRangePreset::LastMonth,
            DateRangePreset::Last30Days,
            DateRangePreset::ThisQuarter,
            DateRangePreset::ThisYear,
            DateRangePreset::Custom,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(['md' => 2, 'xl' => 4])
            ->components([
                ...$this->dateRangeFields(),
                Select::make('source')
                    ->label(__('admin.labels.order_source'))
                    ->options([
                        'sales' => __('admin.options.order_source_sales'),
                        'site' => __('admin.options.order_source_site'),
                        'store' => __('admin.options.order_source_store'),
                    ])
                    ->placeholder(__('admin.labels.all'))
                    ->default(KpiDashboardService::DEFAULT_SOURCE),
            ]);
    }

    public function getFooterWidgets(): array
    {
        return [
            KpiTargetPaceWidget::class,
            KpiStatsWidget::class,
            KpiDailySalesChartWidget::class,
            KpiPaymentMethodsChartWidget::class,
            KpiTopSellersChartWidget::class,
            KpiTopCoursesWidget::class,
            KpiMonthlyTrendChartWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 12,
            'xl' => 12,
        ];
    }
}
