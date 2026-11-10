<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ComplaintServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComplaintServiceRequest extends CreateRecord
{
    protected static string $resource = ComplaintServiceRequestResource::class;
}
