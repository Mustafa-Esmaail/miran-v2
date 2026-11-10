<?php

namespace App\Filament\Admin\Resources\QuizQuestions\Pages;

use App\Filament\Admin\Resources\QuizQuestions\QuizQuestionResource;
use Filament\Resources\Pages\EditRecord;

class EditQuizQuestion extends EditRecord
{
    protected static string $resource = QuizQuestionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
