<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateB2cServiceRequest extends CreateRecord
{
    protected static string $resource = B2cServiceRequestResource::class;
}
