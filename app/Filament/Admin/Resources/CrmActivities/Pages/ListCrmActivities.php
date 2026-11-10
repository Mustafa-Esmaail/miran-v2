<?php

namespace App\Filament\Admin\Resources\CrmActivities\Pages;

use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListCrmActivities extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = CrmActivityResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(__('admin.actions.create'))];
    }
}
