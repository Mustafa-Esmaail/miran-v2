<?php

namespace App\Filament\Admin\Resources\CourseGroups\Pages;

use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseGroup extends CreateRecord
{
    protected static string $resource = CourseGroupResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
