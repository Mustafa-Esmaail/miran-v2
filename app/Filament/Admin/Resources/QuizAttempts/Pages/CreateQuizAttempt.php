<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Pages;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuizAttempt extends CreateRecord
{
    protected static string $resource = QuizAttemptResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
