<?php

namespace App\Filament\Admin\Resources\ImpersonationLogs\Pages;

use App\Filament\Admin\Resources\ImpersonationLogs\ImpersonationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListImpersonationLogs extends ListRecords
{
    protected static string $resource = ImpersonationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
