<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasServiceRequestEditHeaderActions;
use Filament\Resources\Pages\EditRecord;

class EditB2bServiceRequest extends EditRecord
{
    use HasServiceRequestEditHeaderActions;

    protected static string $resource = B2bServiceRequestResource::class;
}
