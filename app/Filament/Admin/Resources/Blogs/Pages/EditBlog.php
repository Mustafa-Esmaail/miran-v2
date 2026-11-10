<?php

namespace App\Filament\Admin\Resources\Blogs\Pages;

use App\Enums\BlogStatus;
use App\Filament\Admin\Resources\Blogs\BlogResource;
use App\Services\BlogService;
use Filament\Resources\Pages\EditRecord;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = app(BlogService::class)->generateSlug(
                $data['title'] ?? $this->record->title,
                $this->record->id
            );
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
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
