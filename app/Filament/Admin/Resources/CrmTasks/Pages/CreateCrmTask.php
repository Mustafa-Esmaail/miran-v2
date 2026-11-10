<?php

namespace App\Filament\Admin\Resources\CrmTasks\Pages;

use App\Filament\Admin\Resources\CrmTasks\CrmTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmTask extends CreateRecord
{
    protected static string $resource = CrmTaskResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
