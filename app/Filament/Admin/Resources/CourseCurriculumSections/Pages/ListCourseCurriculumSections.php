<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\Pages;

use App\Filament\Admin\Resources\CourseCurriculumSections\CourseCurriculumSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCourseCurriculumSections extends ListRecords
{
    protected static string $resource = CourseCurriculumSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
