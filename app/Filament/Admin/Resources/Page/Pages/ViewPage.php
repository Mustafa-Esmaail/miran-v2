<?php

namespace App\Filament\Admin\Resources\Page\Pages;

use App\Filament\Admin\Resources\Page\Actions\PublishNewVersionAction;
use App\Filament\Admin\Resources\Page\PageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

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
            PublishNewVersionAction::make(),
            PublishNewVersionAction::preview(),
            EditAction::make()->label(__('admin.actions.edit')),
        ];
    }
}
