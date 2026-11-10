<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasServiceRequestListHeaderActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListB2cServiceRequests extends ListRecords
{
    use HasHeaderFilters, HasServiceRequestListHeaderActions {
        HasServiceRequestListHeaderActions::getHeaderActions as getServiceRequestHeaderActions;
    }

    protected static string $resource = B2cServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getServiceRequestHeaderActions(),

            Action::make('kanban_view')
                ->label(__('admin.actions.kanban_view'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(fn () => B2cServiceRequestResource::getUrl('kanban')),
        ];
    }
}
