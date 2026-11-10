<?php

namespace App\Filament\Admin\Resources\BlogComments\Pages;

use App\Filament\Admin\Resources\BlogComments\BlogCommentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBlogComment extends ViewRecord
{
    protected static string $resource = BlogCommentResource::class;

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
