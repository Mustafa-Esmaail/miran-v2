<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\Pages;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourseGroupSession extends CreateRecord
{
    protected static string $resource = CourseGroupSessionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
