<?php

namespace App\Filament\Admin\Resources\Qualifications\Pages;

use App\Filament\Admin\Resources\Qualifications\QualificationResource;
use Filament\Resources\Pages\EditRecord;

class EditQualification extends EditRecord
{
    protected static string $resource = QualificationResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->record->getTranslation('name', app()->getLocale())]);
    }
}
