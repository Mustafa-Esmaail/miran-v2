<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Pages;

use App\Filament\Admin\Resources\ServiceRequestTypes\ServiceRequestTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequestType extends CreateRecord
{
    protected static string $resource = ServiceRequestTypeResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
