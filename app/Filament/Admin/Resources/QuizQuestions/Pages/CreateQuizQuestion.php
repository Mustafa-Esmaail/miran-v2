<?php

namespace App\Filament\Admin\Resources\QuizQuestions\Pages;

use App\Filament\Admin\Resources\QuizQuestions\QuizQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuizQuestion extends CreateRecord
{
    protected static string $resource = QuizQuestionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
