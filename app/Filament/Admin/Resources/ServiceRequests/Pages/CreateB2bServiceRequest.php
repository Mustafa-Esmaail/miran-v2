<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateB2bServiceRequest extends CreateRecord
{
    protected static string $resource = B2bServiceRequestResource::class;
}
