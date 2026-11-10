<?php

namespace App\Filament\Admin\Resources\CrmActivities\Pages;

use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCrmActivity extends ViewRecord
{
    protected static string $resource = CrmActivityResource::class;

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
