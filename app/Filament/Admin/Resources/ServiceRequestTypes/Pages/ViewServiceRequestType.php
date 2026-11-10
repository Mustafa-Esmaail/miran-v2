<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Pages;

use App\Filament\Admin\Resources\ServiceRequestTypes\ServiceRequestTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequestType extends ViewRecord
{
    protected static string $resource = ServiceRequestTypeResource::class;

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
