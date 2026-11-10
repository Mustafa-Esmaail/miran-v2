<?php

namespace App\Filament\Admin\Resources\BlogComments\Pages;

use App\Filament\Admin\Resources\BlogComments\BlogCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListBlogComments extends ListRecords
{
    protected static string $resource = BlogCommentResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
