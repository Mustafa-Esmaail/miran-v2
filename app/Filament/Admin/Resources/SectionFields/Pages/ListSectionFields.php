<?php

namespace App\Filament\Admin\Resources\SectionFields\Pages;

use App\Filament\Admin\Resources\SectionFields\SectionFieldResource;
use Filament\Resources\Pages\ListRecords;

class ListSectionFields extends ListRecords
{
    protected static string $resource = SectionFieldResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
