<?php

namespace App\Filament\Admin\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Sections\SectionResource;
use App\Models\Section;
use App\Support\SectionFieldFormData;
use Filament\Resources\Pages\EditRecord;

class EditSection extends EditRecord
{
    protected static string $resource = SectionResource::class;

    protected array $fieldPayloads = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        if ($record instanceof Section) {
            $data['content'] = SectionFieldFormData::formFromFields($record->sectionFields);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $content = is_array($data['content'] ?? null) ? $data['content'] : [];
        $this->fieldPayloads = SectionFieldFormData::fieldsFromForm($content);
        unset($data['content']);

        return $data;
    }

    protected function afterSave(): void
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
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
