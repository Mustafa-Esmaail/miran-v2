<?php

namespace App\Filament\Admin\Resources\CourseGroups\Pages;

use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseGroups extends ListRecords
{
    protected static string $resource = CourseGroupResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
