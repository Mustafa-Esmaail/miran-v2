<?php

namespace App\Filament\Admin\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Sections\SectionResource;
use App\Models\Section;
use App\Support\SectionFieldFormData;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    protected array $fieldPayloads = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];
        $this->fieldPayloads = SectionFieldFormData::fieldsFromForm($content);
        unset($data['content']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        if (! $record instanceof Section) {
            return;
        }

        foreach ($this->fieldPayloads as $key => $payload) {
            $record->sectionFields()->updateOrCreate(
                ['key' => $key],
                [
                    'type' => $payload['type'],
                    'value' => $payload['value'],
                    'sort_order' => 0,
                    'group' => null,
                ]
            );
        }
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
