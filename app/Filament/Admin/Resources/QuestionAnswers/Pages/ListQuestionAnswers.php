<?php

namespace App\Filament\Admin\Resources\QuestionAnswers\Pages;

use App\Filament\Admin\Resources\QuestionAnswers\QuestionAnswerResource;
use Filament\Resources\Pages\ListRecords;

class ListQuestionAnswers extends ListRecords
{
    protected static string $resource = QuestionAnswerResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
