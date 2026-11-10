<?php

namespace Database\Seeders;

use App\Enums\AttachmentVisibility;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use Illuminate\Database\Seeder;

class CourseAttachmentSeeder extends Seeder
{
    public function run(): void
    {
        Course::query()
            ->with('curriculumSections')
            ->each(function (Course $course): void {
                $sections = $course->curriculumSections->sortBy('sort_order')->values();

                if ($sections->isEmpty()) {
                    return;
                }

                $attachments = [
                    [
                        'title'      => ['en' => 'Course Materials', 'ar' => 'مواد الدورة'],
                        'files'      => [
                            'files/course-outline.pdf',
                            'files/course-slides.pdf',
                            'files/course-notes.pdf',
                        ],
                        'visibility' => AttachmentVisibility::Public,
                    ],
                    [
                        'title'      => ['en' => 'Workshop Resources', 'ar' => 'موارد ورشة العمل'],
                        'files'      => [
                            'files/workbook.pdf',
                            'files/exercises.pdf',
                        ],
                        'visibility' => AttachmentVisibility::EnrolledOnly,
                    ],
                    [
                        'title'      => ['en' => 'Reference Documents', 'ar' => 'المستندات المرجعية'],
                        'files'      => [
                            'files/reference-guide.pdf',
                        ],
                        'visibility' => AttachmentVisibility::EnrolledOnly,
                    ],
                ];

                foreach ($attachments as $index => $data) {
                    $section = $sections[$index % $sections->count()];

                    CourseAttachment::updateOrCreate(
                        [
                            'course_id'                    => $course->id,
                            'course_curriculum_section_id' => $section->id,
                            'title->en'                    => $data['title']['en'],
                        ],
                        [
                            'title'      => $data['title'],
                            'file_path'  => $data['files'],
                            'visibility' => $data['visibility'],
                        ]
                    );
                }
            });
    }
}
