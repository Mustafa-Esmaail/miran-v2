<?php

namespace App\Filament\Admin\Resources\ServiceRequestStages\Pages;

use App\Filament\Admin\Resources\ServiceRequestStages\ServiceRequestStageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequestStage extends CreateRecord
{
    protected static string $resource = ServiceRequestStageResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
