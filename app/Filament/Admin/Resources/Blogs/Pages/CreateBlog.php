<?php

namespace App\Filament\Admin\Resources\Blogs\Pages;

use App\Enums\BlogStatus;
use App\Filament\Admin\Resources\Blogs\BlogResource;
use App\Services\BlogService;
use Filament\Resources\Pages\CreateRecord;

class CreateBlog extends CreateRecord
{
    protected static string $resource = BlogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = app(BlogService::class)->generateSlug($data['title'] ?? '');
        }

        if (empty($data['author_id'])) {
            $data['author_id'] = auth('admin')->id();
        }

        if (! empty($data['long_description'])) {
            $data['content'] = $data['long_description'];
        } elseif (! empty($data['short_description'])) {
            $data['content'] = $data['short_description'];
        }

        if (! empty($data['published_at']) && ($data['status'] ?? null) === BlogStatus::Draft->value) {
            $data['status'] = BlogStatus::Published->value;
        }

        return $data;
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
