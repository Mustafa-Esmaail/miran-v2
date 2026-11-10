<?php

namespace App\Filament\Admin\Resources\SalesTargets\Pages;

use App\Filament\Admin\Resources\SalesTargets\SalesTargetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesTarget extends ViewRecord
{
    protected static string $resource = SalesTargetResource::class;

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
