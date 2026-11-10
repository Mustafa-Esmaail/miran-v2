<?php

namespace App\Filament\Admin\Resources\QuestionAnswers\Pages;

use App\Filament\Admin\Resources\QuestionAnswers\QuestionAnswerResource;
use Filament\Resources\Pages\EditRecord;

class EditQuestionAnswer extends EditRecord
{
    protected static string $resource = QuestionAnswerResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
