<?php

namespace App\Filament\Admin\Resources\QuizQuestions\Pages;

use App\Filament\Admin\Resources\QuizQuestions\QuizQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizQuestions extends ListRecords
{
    protected static string $resource = QuizQuestionResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
