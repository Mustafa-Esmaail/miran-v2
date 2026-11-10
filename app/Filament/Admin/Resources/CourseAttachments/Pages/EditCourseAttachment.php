<?php

namespace App\Filament\Admin\Resources\CourseAttachments\Pages;

use App\Filament\Admin\Resources\CourseAttachments\CourseAttachmentResource;
use App\Models\CourseCurriculumSection;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCourseAttachment extends EditRecord
{
    protected static string $resource = CourseAttachmentResource::class;

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
        $section = CourseCurriculumSection::find($data['course_curriculum_section_id'] ?? null);
        $data['course_group_id'] = $section?->course_group_id;

        // course_id is a real column already in $data; extract flat title fields for the form
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
            'course_curriculum_section_id' => $data['course_curriculum_section_id'] ?? null,
            'visibility' => $data['visibility'],
            'file_path' => $data['file_path'] ?? $record->file_path,
            'title' => [
                'ar' => trim($data['title_ar'] ?? ''),
                'en' => trim($data['title_en'] ?? ''),
            ],
        ]);

        return $record;
    }
}
