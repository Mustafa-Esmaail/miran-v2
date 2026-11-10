<?php

namespace App\Filament\Admin\Resources\NelcSyncLogs\Pages;

use App\Filament\Admin\Resources\NelcSyncLogs\NelcSyncLogResource;
use Filament\Resources\Pages\ListRecords;

class ListNelcSyncLogs extends ListRecords
{
    protected static string $resource = NelcSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
