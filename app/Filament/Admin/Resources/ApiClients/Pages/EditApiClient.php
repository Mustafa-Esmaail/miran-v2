<?php

namespace App\Filament\Admin\Resources\ApiClients\Pages;

use App\Filament\Admin\Resources\ApiClients\ApiClientResource;
use Filament\Resources\Pages\EditRecord;

class EditApiClient extends EditRecord
{
    protected static string $resource = ApiClientResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->record->name]);
    }
}
