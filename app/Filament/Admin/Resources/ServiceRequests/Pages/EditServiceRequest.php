<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasServiceRequestEditHeaderActions;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    use HasServiceRequestEditHeaderActions;

    protected static string $resource = ServiceRequestResource::class;
}
