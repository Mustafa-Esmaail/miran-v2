<?php

namespace App\Filament\Admin\Resources\CourseQuestions\Pages;

use App\Filament\Admin\Resources\CourseQuestions\CourseQuestionResource;
use Filament\Resources\Pages\EditRecord;

class EditCourseQuestion extends EditRecord
{
    protected static string $resource = CourseQuestionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
