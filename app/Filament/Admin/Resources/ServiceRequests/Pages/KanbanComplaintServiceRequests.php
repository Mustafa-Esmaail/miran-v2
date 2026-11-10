<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ComplaintServiceRequestResource;

class KanbanComplaintServiceRequests extends ServiceRequestKanbanBoard
{
    protected static string $resource = ComplaintServiceRequestResource::class;

    protected function relevantTypeSlugs(): array
    {
        return ['shakawi'];
    }
}
