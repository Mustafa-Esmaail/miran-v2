<?php

namespace App\Filament\Admin\Resources\CourseAttachments\Pages;

use App\Filament\Admin\Resources\CourseAttachments\CourseAttachmentResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseAttachments extends ListRecords
{
    protected static string $resource = CourseAttachmentResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
