<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\Pages;

use App\Filament\Admin\Resources\CourseCurriculumSections\CourseCurriculumSectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCourseCurriculumSection extends ViewRecord
{
    protected static string $resource = CourseCurriculumSectionResource::class;

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
            EditAction::make()->label(__('admin.actions.edit')),
        ];
    }
}
