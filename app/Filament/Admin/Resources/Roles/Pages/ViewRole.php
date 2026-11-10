<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

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
