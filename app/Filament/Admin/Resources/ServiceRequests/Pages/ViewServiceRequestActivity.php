<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestActivityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequestActivity extends ViewRecord
{
    protected static string $resource = ServiceRequestActivityResource::class;

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
