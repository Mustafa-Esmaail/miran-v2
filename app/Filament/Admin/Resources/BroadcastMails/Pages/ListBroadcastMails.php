<?php

namespace App\Filament\Admin\Resources\BroadcastMails\Pages;

use App\Filament\Admin\Resources\BroadcastMails\BroadcastMailResource;
use Filament\Resources\Pages\ListRecords;

class ListBroadcastMails extends ListRecords
{
    protected static string $resource = BroadcastMailResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
