<?php

namespace App\Filament\Admin\Resources\CrmActivities\Pages;

use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use Filament\Resources\Pages\EditRecord;

class EditCrmActivity extends EditRecord
{
    protected static string $resource = CrmActivityResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
