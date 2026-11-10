<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestActivityResource;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListServiceRequestActivities extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = ServiceRequestActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
