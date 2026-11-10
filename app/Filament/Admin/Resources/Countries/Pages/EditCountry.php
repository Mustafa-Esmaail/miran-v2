<?php

namespace App\Filament\Admin\Resources\Countries\Pages;

use App\Filament\Admin\Resources\Countries\CountryResource;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->record->getTranslation('name', app()->getLocale())]);
    }
}
