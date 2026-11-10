<?php

namespace App\Filament\Admin\Resources\Page\Pages;

use App\Filament\Admin\Resources\Page\Actions\PublishNewVersionAction;
use App\Filament\Admin\Resources\Page\PageResource;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishNewVersionAction::make(),
            PublishNewVersionAction::preview(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
