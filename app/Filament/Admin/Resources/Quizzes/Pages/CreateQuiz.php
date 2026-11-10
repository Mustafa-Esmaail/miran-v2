<?php

namespace App\Filament\Admin\Resources\Quizzes\Pages;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
