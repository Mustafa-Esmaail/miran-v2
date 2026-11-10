<?php

namespace App\Filament\Admin\Resources\ShareLinks\Pages;

use App\Filament\Admin\Resources\ShareLinks\ShareLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShareLinks extends ListRecords
{
    protected static string $resource = ShareLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
        ];
    }
}
