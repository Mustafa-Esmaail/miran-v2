<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Pages;

use App\Filament\Admin\Resources\ServiceRequestTypes\ServiceRequestTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequestTypes extends ListRecords
{
    protected static string $resource = ServiceRequestTypeResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
