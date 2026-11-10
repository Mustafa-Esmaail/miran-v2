<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ComplaintServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasServiceRequestEditHeaderActions;
use Filament\Resources\Pages\EditRecord;

class EditComplaintServiceRequest extends EditRecord
{
    use HasServiceRequestEditHeaderActions;

    protected static string $resource = ComplaintServiceRequestResource::class;
}
