<?php

namespace App\Filament\Admin\Resources\Qualifications\Pages;

use App\Filament\Admin\Resources\Qualifications\QualificationResource;
use Filament\Resources\Pages\ListRecords;

class ListQualifications extends ListRecords
{
    protected static string $resource = QualificationResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
