<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;

class KanbanServiceRequests extends ServiceRequestKanbanBoard
{
    protected static string $resource = ServiceRequestResource::class;

    protected function relevantTypeSlugs(): array
    {
        return [];
    }
}
