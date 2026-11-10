<?php

namespace App\Filament\Admin\Resources\Trainers\Pages;

use App\Filament\Admin\Resources\Trainers\TrainerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainer extends CreateRecord
{
    protected static string $resource = TrainerResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        if (! $record instanceof User) {
            return;
        }

        $record->assignRole('instructor');
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
