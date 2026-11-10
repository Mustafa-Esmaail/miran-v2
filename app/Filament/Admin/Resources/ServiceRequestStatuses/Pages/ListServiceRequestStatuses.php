<?php

namespace App\Filament\Admin\Resources\ServiceRequestStatuses\Pages;

use App\Filament\Admin\Resources\ServiceRequestStatuses\ServiceRequestStatusResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequestStatuses extends ListRecords
{
    protected static string $resource = ServiceRequestStatusResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
