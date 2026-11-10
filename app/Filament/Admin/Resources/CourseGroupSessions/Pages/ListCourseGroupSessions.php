<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\Pages;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseGroupSessions extends ListRecords
{
    protected static string $resource = CourseGroupSessionResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
