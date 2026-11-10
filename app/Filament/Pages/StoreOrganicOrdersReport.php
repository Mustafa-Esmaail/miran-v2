<?php

namespace App\Filament\Pages;

use App\Enums\OrderSource;
use Illuminate\Database\Eloquent\Builder;

class StoreOrganicOrdersReport extends OrderLineItemsReport
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.store-organic-orders-report';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.store_organic_orders_report');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('store_organic_orders_report.viewAny') ?? false;
    }

    public function getTitle(): string
    {
        return __('admin.navigation.store_organic_orders_report');
    }

    protected function exportName(): string
    {
        return 'store-organic-orders-report';
    }

    protected function getLockedSource(): array
    {
        return [OrderSource::Store->value];
    }

    protected function baseQuery(): Builder
    {
        return parent::baseQuery()->whereHas('order', fn (Builder $q): Builder => $q->whereIn('source', $this->getLockedSource()));
    }
}
