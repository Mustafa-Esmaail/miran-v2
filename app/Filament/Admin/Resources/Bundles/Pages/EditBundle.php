<?php

namespace App\Filament\Admin\Resources\Bundles\Pages;

use App\Filament\Admin\Resources\Bundles\BundleResource;
use Filament\Resources\Pages\EditRecord;

class EditBundle extends EditRecord
{
    protected static string $resource = BundleResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
