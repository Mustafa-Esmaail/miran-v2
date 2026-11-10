<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;

class KanbanB2cServiceRequests extends ServiceRequestKanbanBoard
{
    protected static string $resource = B2cServiceRequestResource::class;

    protected function relevantTypeSlugs(): array
    {
        return ['tlbat'];
    }
}
