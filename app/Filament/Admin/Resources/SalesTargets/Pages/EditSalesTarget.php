<?php

namespace App\Filament\Admin\Resources\SalesTargets\Pages;

use App\Filament\Admin\Resources\SalesTargets\SalesTargetResource;
use Filament\Resources\Pages\EditRecord;

class EditSalesTarget extends EditRecord
{
    protected static string $resource = SalesTargetResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
