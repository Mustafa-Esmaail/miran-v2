<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;

class KanbanB2bServiceRequests extends ServiceRequestKanbanBoard
{
    protected static string $resource = B2bServiceRequestResource::class;

    protected function relevantTypeSlugs(): array
    {
        return ['tlbat'];
    }
}
