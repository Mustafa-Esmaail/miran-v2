<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\Pages;

use App\Filament\Admin\Resources\CourseCurriculumSections\CourseCurriculumSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseCurriculumSection extends EditRecord
{
    protected static string $resource = CourseCurriculumSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
