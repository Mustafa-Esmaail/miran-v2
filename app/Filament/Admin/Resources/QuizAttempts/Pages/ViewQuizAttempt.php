<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Pages;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use App\Models\QuizAttempt;
use App\ViewModels\QuizAttemptAnswersViewModel;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ViewQuizAttempt extends Page
{
    protected static string $resource = QuizAttemptResource::class;

    public QuizAttempt $record;

    public function getView(): string
    {
        return 'filament.pages.quiz-attempt-view';
    }

    public function mount(int|string $record): void
    {
        $this->record = QuizAttempt::query()
            ->with(['quiz.questions.options', 'user'])
            ->findOrFail($record);

        abort_unless(QuizAttemptResource::canView($this->record), 403);
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.quiz_attempt_view');
    }

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.quiz_attempt_view');
    }

    public function getAnswersData(): array
    {
        return QuizAttemptAnswersViewModel::fromAttempt($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('admin.actions.go_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(QuizAttemptResource::getUrl('index')),
            Action::make('edit')
                ->label(__('admin.actions.edit'))
                ->icon('heroicon-o-pencil')
                ->url(QuizAttemptResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
