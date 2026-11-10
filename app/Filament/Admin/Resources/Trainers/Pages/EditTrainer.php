<?php

namespace App\Filament\Admin\Resources\Trainers\Pages;

use App\Filament\Admin\Resources\Trainers\TrainerResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditTrainer extends EditRecord
{
    protected static string $resource = TrainerResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;
        if (! $record instanceof User) {
            return;
        }

        if (! $record->hasRole('instructor')) {
            $record->assignRole('instructor');
        }
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
