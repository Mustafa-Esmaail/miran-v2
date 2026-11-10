<?php

namespace App\Filament\Admin\Resources\SectionFields\Pages;

use App\Filament\Admin\Resources\SectionFields\SectionFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSectionField extends CreateRecord
{
    protected static string $resource = SectionFieldResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
