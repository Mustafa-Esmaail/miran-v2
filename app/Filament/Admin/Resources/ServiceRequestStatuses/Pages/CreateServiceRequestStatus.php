<?php

namespace App\Filament\Admin\Resources\ServiceRequestStatuses\Pages;

use App\Filament\Admin\Resources\ServiceRequestStatuses\ServiceRequestStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequestStatus extends CreateRecord
{
    protected static string $resource = ServiceRequestStatusResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
