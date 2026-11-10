<?php

namespace App\Filament\Admin\Resources\CourseAttachments\Pages;

use App\Filament\Admin\Resources\CourseAttachments\CourseAttachmentResource;
use App\Models\CourseAttachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourseAttachment extends CreateRecord
{
    protected static string $resource = CourseAttachmentResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return CourseAttachment::create([
            'course_id' => $data['course_id'],
            'course_curriculum_section_id' => $data['course_curriculum_section_id'] ?? null,
            'visibility' => $data['visibility'],
            'file_path' => $data['file_path'] ?? [],
            'title' => [
                'ar' => trim($data['title_ar'] ?? ''),
                'en' => trim($data['title_en'] ?? ''),
            ],
        ]);
    }
}
