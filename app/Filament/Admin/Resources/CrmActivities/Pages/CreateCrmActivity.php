<?php

namespace App\Filament\Admin\Resources\CrmActivities\Pages;

use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmActivity extends CreateRecord
{
    protected static string $resource = CrmActivityResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
