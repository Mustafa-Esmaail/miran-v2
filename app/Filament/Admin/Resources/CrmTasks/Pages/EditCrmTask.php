<?php

namespace App\Filament\Admin\Resources\CrmTasks\Pages;

use App\Filament\Admin\Resources\CrmTasks\CrmTaskResource;
use Filament\Resources\Pages\EditRecord;

class EditCrmTask extends EditRecord
{
    protected static string $resource = CrmTaskResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
