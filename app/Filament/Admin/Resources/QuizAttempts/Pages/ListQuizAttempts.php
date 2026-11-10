<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Pages;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListQuizAttempts extends ListRecords
{
    protected static string $resource = QuizAttemptResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
