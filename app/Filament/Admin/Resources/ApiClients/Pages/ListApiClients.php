<?php

namespace App\Filament\Admin\Resources\ApiClients\Pages;

use App\Filament\Admin\Resources\ApiClients\ApiClientResource;
use Filament\Resources\Pages\ListRecords;

class ListApiClients extends ListRecords
{
    protected static string $resource = ApiClientResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
