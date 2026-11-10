<?php

namespace App\Filament\Admin\Resources\CourseQuestions\Pages;

use App\Filament\Admin\Resources\CourseQuestions\CourseQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListCourseQuestions extends ListRecords
{
    protected static string $resource = CourseQuestionResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
