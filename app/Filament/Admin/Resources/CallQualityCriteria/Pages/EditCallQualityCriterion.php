<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria\Pages;

use App\Filament\Admin\Resources\CallQualityCriteria\CallQualityCriterionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCallQualityCriterion extends EditRecord
{
    protected static string $resource = CallQualityCriterionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
