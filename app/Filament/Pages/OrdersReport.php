<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Filament\Widgets\OrdersReportIncomeChartWidget;
use App\Filament\Widgets\OrdersReportStatsWidget;
use App\Filament\Widgets\OrdersReportTopEmployeesWidget;
use App\Filament\Widgets\OrdersReportTopItemsWidget;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class OrdersReport extends Page
{
    use HasDateRangePresets;
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.orders-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.orders_report');
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
        return auth('admin')->user()?->can('orders_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.orders_report');
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Today;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                ...$this->dateRangeFields(),
                Select::make('payment_method')
                    ->label(__('admin.labels.payment_method'))
                    ->options([
                        'bank_transfer' => __('admin.labels.bank_transfer_provider'),
                        'tamara' => 'Tamara',
                        'cod' => __('admin.labels.cod'),
                    ])
                    ->placeholder(__('admin.labels.all')),
                Select::make('source')
                    ->label(__('admin.labels.order_source'))
                    ->options([
                        'site' => __('admin.options.order_source_site'),
                        'store' => __('admin.options.order_source_store'),
                        'sales' => __('admin.options.order_source_sales'),
                    ])
                    ->placeholder(__('admin.labels.all')),
                Select::make('status')
                    ->label(__('admin.labels.status'))
                    ->options([
                        'pending' => __('admin.options.pending'),
                        'paid' => __('admin.options.paid'),
                        'rejected' => __('admin.options.rejected'),
                        'refunded' => __('admin.options.refunded'),
                        'cancelled' => __('admin.options.cancelled'),
                    ])
                    ->placeholder(__('admin.labels.all'))
                    ->default('paid'),
            ]);
    }

    public function getFooterWidgets(): array
    {
        return [
            OrdersReportStatsWidget::class,
            OrdersReportIncomeChartWidget::class,
            OrdersReportTopItemsWidget::class,
            OrdersReportTopEmployeesWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }
}
