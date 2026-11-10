<?php

namespace App\Filament\Admin\Resources\SalesTargets\Pages;

use App\Filament\Admin\Resources\SalesTargets\SalesTargetResource;
use App\Filament\Widgets\SalesTargetProgressWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListSalesTargets extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = SalesTargetResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesTargetProgressWidget::class,
        ];
    }
}
