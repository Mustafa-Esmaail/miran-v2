<?php

namespace App\Filament\Pages\Concerns;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

trait HasOrderReportFilters
{
    use HasFiltersForm;

    /**
     * @return string[]|string|null
     */
    protected function getLockedSource(): array|string|null
    {
        return null;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                DatePicker::make('date_from')->label(__('admin.labels.from'))->default(today()),
                DatePicker::make('date_to')->label(__('admin.labels.until')),
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
                    ->visible($this->getLockedSource() === null)
                    ->options([
                        'site' => __('admin.options.order_source_site'),
                        'store' => __('admin.options.order_source_store'),
                        'sales' => __('admin.options.order_source_sales'),
                    ])
                    ->placeholder(__('admin.labels.all'))
                    ->default('sales'),
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

    protected function reportFilterValue(string $key): ?string
    {
        return $this->filters[$key] ?? null;
    }

    /**
     * @return string[]|string|null
     */
    protected function reportSourceFilterValue(): array|string|null
    {
        return $this->getLockedSource() ?? $this->reportFilterValue('source');
    }
}
