<?php

namespace App\Filament\Admin\Resources\Students\Pages;

use App\Filament\Admin\Resources\Students\StudentResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label(__('admin.actions.login_as_student'))
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('gray')
                ->visible(fn (): bool => (auth('admin')->user()?->can('students.impersonate') ?? false) && ($this->getRecord() instanceof User) && $this->getRecord()->canBeImpersonated())
                ->requiresConfirmation()
                ->modalDescription(__('admin.modals.impersonate_student.description'))
                ->url(fn (): string => route('impersonate', ['id' => $this->getRecord()->getKey(), 'guardName' => 'web']))
                ->openUrlInNewTab(false),
            EditAction::make()->label(__('admin.actions.edit')),
        ];
    }
}
