<?php

namespace App\Filament\Admin\Resources\Assignments\Pages;

use App\Filament\Admin\Resources\Assignments\AssignmentResource;
use App\Models\Assignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

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
        return Assignment::create([
            'course_id' => $data['course_id'],
            'course_group_id' => $data['course_group_id'] ?? null,
            'course_curriculum_section_id' => $data['course_curriculum_section_id'],
            'description' => $data['description'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? [],
            'due_at' => $data['due_at'] ?? null,
            'max_grade' => $data['max_grade'] ?? 10,
            'is_published' => $data['is_published'] ?? true,
            'title' => [
                'ar' => trim($data['title_ar'] ?? ''),
                'en' => trim($data['title_en'] ?? ''),
            ],
        ]);
    }
}
