<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Pages;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use Filament\Resources\Pages\EditRecord;

class EditQuizAttempt extends EditRecord
{
    protected static string $resource = QuizAttemptResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
