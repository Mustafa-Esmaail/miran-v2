<?php

namespace App\Filament\Admin\Resources\NotificationLogs\Pages;

use App\Filament\Admin\Resources\NotificationLogs\NotificationLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationLog extends ViewRecord
{
    protected static string $resource = NotificationLogResource::class;

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
