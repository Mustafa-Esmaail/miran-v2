<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Pages;

use App\Filament\Admin\Resources\ServiceRequestTypes\ServiceRequestTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequestType extends EditRecord
{
    protected static string $resource = ServiceRequestTypeResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
