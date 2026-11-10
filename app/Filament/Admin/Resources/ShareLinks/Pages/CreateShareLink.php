<?php

namespace App\Filament\Admin\Resources\ShareLinks\Pages;

use App\Filament\Admin\Resources\ShareLinks\ShareLinkResource;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Support\CrmScope;
use Filament\Resources\Pages\CreateRecord;

class CreateShareLink extends CreateRecord
{
    protected static string $resource = ShareLinkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $locale = app()->getLocale();

        if (CrmScope::isLimitedToSelf()) {
            $data['employee_id'] = CrmScope::viewer()->id;
        }

        if ($data['target_type'] === 'course' && ! empty($data['target_id'])) {
            $course = Course::find($data['target_id']);
            $data['target_label'] = $course?->getTranslation('title', $locale) ?: $course?->slug;
        } elseif ($data['target_type'] === 'bundle' && ! empty($data['target_id'])) {
            $bundle = Bundle::find($data['target_id']);
            $data['target_label'] = $bundle?->getTranslation('title', $locale) ?: $bundle?->slug;
        } elseif ($data['target_type'] === 'category' && ! empty($data['target_id'])) {
            $category = Category::find($data['target_id']);
            $data['target_label'] = $category?->getTranslation('name', $locale) ?: $category?->slug;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
