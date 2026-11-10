<?php

namespace App\Filament\Admin\Resources\NelcSyncLogs\Pages;

use App\Filament\Admin\Resources\NelcSyncLogs\NelcSyncLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewNelcSyncLog extends ViewRecord
{
    protected static string $resource = NelcSyncLogResource::class;

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
        return [];
    }
}
