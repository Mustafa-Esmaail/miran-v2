<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CourseContentDaysSeeder extends Seeder
{
    public function run(): void
    {
        Course::query()
            ->with([
                'curriculumSections',
                'lessons',
                'attachments',
                'groups.sessions',
            ])
            ->each(function (Course $course): void {
                $sections = collect(range(1, 3))
                    ->map(fn (int $dayNumber): CourseCurriculumSection => CourseCurriculumSection::query()->updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'sort_order' => $dayNumber,
                        ],
                        [
                            'title' => [
                                'ar' => 'اليوم '.$dayNumber,
                                'en' => 'Day '.$dayNumber,
                            ],
                            'description' => [
                                'ar' => 'محتوى اليوم '.$dayNumber,
                                'en' => 'Content for day '.$dayNumber,
                            ],
                        ]
                    ));

                $this->assignLessonsToDays(
                    $course->lessons
                        ->sortBy(fn (Lesson $lesson): int => $lesson->sort_order ?? $lesson->id)
                        ->values(),
                    $sections
                );

                $this->assignAttachmentsToDays(
                    $course->attachments
                        ->sortBy('id')
                        ->values(),
                    $sections
                );

                $this->assignSessionsToDays(
                    $course->groups
                        ->flatMap->sessions
                        ->sortBy(fn (CourseGroupSession $session): string => ($session->start_at?->toDateTimeString() ?? '').'-'.$session->id)
                        ->values(),
                    $sections
                );

                $course->forceFill(['days_count' => 3])->save();

                CourseCurriculumSection::query()
                    ->where('course_id', $course->id)
                    ->whereNotIn('id', $sections->pluck('id'))
                    ->delete();
            });
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @param  Collection<int, CourseCurriculumSection>  $sections
     */
    private function assignLessonsToDays(Collection $lessons, Collection $sections): void
    {
        $lessons->each(function (Lesson $lesson, int $index) use ($sections): void {
            $section = $sections[$index % 3];

            $lesson->forceFill([
                'course_curriculum_section_id' => $section->id,
            ])->save();
        });
    }

    /**
     * @param  Collection<int, CourseAttachment>  $attachments
     * @param  Collection<int, CourseCurriculumSection>  $sections
     */
    private function assignAttachmentsToDays(Collection $attachments, Collection $sections): void
    {
        $attachments->each(function (CourseAttachment $attachment, int $index) use ($sections): void {
            $section = $sections[$index % 3];

            $attachment->forceFill([
                'course_curriculum_section_id' => $section->id,
            ])->save();
        });
    }

    /**
     * @param  Collection<int, CourseGroupSession>  $sessions
     * @param  Collection<int, CourseCurriculumSection>  $sections
     */
    private function assignSessionsToDays(Collection $sessions, Collection $sections): void
    {
        $sessions->each(function (CourseGroupSession $session, int $index) use ($sections): void {
            $section = $sections[$index % 3];

            $session->forceFill([
                'course_curriculum_section_id' => $section->id,
            ])->save();
        });
    }
}
