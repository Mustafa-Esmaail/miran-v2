<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasServiceRequestListHeaderActions;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListServiceRequests extends ListRecords
{
    use HasHeaderFilters, HasServiceRequestListHeaderActions {
        HasServiceRequestListHeaderActions::getHeaderActions as getServiceRequestHeaderActions;
    }

    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getServiceRequestHeaderActions(),

            Action::make('kanban_view')
                ->label(__('admin.actions.kanban_view'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(fn () => ServiceRequestResource::getUrl('kanban')),
        ];
    }
}
