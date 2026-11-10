<?php

namespace App\Filament\Admin\Resources\ServiceRequestStages\Pages;

use App\Filament\Admin\Resources\ServiceRequestStages\ServiceRequestStageResource;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequestStage extends EditRecord
{
    protected static string $resource = ServiceRequestStageResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
