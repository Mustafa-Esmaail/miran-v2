<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;
}
