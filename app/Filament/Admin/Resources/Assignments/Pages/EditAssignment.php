<?php

namespace App\Filament\Admin\Resources\Assignments\Pages;

use App\Filament\Admin\Resources\Assignments\AssignmentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $title = $data['title'] ?? [];
        if (is_string($title)) {
            $title = json_decode($title, true) ?? [];
        }
        $data['title_ar'] = $title['ar'] ?? '';
        $data['title_en'] = $title['en'] ?? '';

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update([
            'course_id' => $data['course_id'],
            'course_group_id' => $data['course_group_id'] ?? null,
            'course_curriculum_section_id' => $data['course_curriculum_section_id'],
            'description' => $data['description'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? $record->attachment_path,
            'due_at' => $data['due_at'] ?? null,
            'max_grade' => $data['max_grade'] ?? $record->max_grade,
            'is_published' => $data['is_published'] ?? $record->is_published,
            'title' => [
                'ar' => trim($data['title_ar'] ?? ''),
                'en' => trim($data['title_en'] ?? ''),
            ],
        ]);

        return $record;
    }
}
