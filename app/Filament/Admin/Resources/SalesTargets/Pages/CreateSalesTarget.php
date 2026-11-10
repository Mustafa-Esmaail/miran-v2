<?php

namespace App\Filament\Admin\Resources\SalesTargets\Pages;

use App\Filament\Admin\Resources\SalesTargets\SalesTargetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesTarget extends CreateRecord
{
    protected static string $resource = SalesTargetResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
