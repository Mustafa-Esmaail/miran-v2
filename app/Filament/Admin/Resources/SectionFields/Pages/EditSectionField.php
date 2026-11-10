<?php

namespace App\Filament\Admin\Resources\SectionFields\Pages;

use App\Filament\Admin\Resources\SectionFields\SectionFieldResource;
use Filament\Resources\Pages\EditRecord;

class EditSectionField extends EditRecord
{
    protected static string $resource = SectionFieldResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
