<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria\Pages;

use App\Filament\Admin\Resources\CallQualityCriteria\CallQualityCriterionResource;
use App\Models\CallQualityCriterion;
use Filament\Resources\Pages\CreateRecord;

class CreateCallQualityCriterion extends CreateRecord
{
    protected static string $resource = CallQualityCriterionResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sort_order'] = ((int) CallQualityCriterion::query()->max('sort_order')) + 1;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
