<?php

namespace App\Services\Admin;

use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseGroupCloneService
{
    /**
     * @param  array{
     *   course_id:int,
     *   title_ar?:string|null,
     *   title_en?:string|null,
     *   group_price_amount?:int|null,
     *   capacity?:int|null,
     *   start_at?:string|null,
     *   end_at?:string|null,
     *   enrollment_deadline_at?:string|null,
     *   status?:string|null,
     *   copy_curriculum_sections?:bool,
     *   copy_sessions?:bool,
     *   copy_lessons?:bool,
     *   copy_attachments?:bool
     * } $data
     */
    public function clone(CourseGroup $sourceGroup, array $data): CourseGroup
    {
        $targetCourseId = (int) $data['course_id'];
        $sourceCourseId = (int) $sourceGroup->course_id;
        $isCrossCourseClone = $targetCourseId !== $sourceCourseId;

        $copyCurriculumSections = (bool) ($data['copy_curriculum_sections'] ?? true);
        $copySessions = (bool) ($data['copy_sessions'] ?? true);
        $copyLessons = (bool) ($data['copy_lessons'] ?? false);
        $copyAttachments = (bool) ($data['copy_attachments'] ?? false);

        if ($isCrossCourseClone && ! $copyCurriculumSections && ($copySessions || $copyLessons || $copyAttachments)) {
            throw ValidationException::withMessages([
                'copy_curriculum_sections' => __('admin.validation.copy_curriculum_required_for_cross_course'),
            ]);
        }

        return DB::transaction(function () use (
            $sourceGroup,
            $data,
            $targetCourseId,
            $sourceCourseId,
            $isCrossCourseClone,
            $copyCurriculumSections,
            $copySessions,
            $copyLessons,
            $copyAttachments
        ): CourseGroup {
            $newGroup = CourseGroup::query()->create([
                'course_id' => $targetCourseId,
                'title' => [
                    'ar' => $this->resolveTitleValue($data, $sourceGroup, 'ar'),
                    'en' => $this->resolveTitleValue($data, $sourceGroup, 'en'),
                ],
                'group_price_amount' => $data['group_price_amount'] ?? $sourceGroup->group_price_amount,
                'capacity' => $data['capacity'] ?? $sourceGroup->capacity,
                'start_at' => $data['start_at'] ?? $sourceGroup->start_at,
                'end_at' => $data['end_at'] ?? $sourceGroup->end_at,
                'enrollment_deadline_at' => $data['enrollment_deadline_at'] ?? $sourceGroup->enrollment_deadline_at,
                'status' => $data['status'] ?? (string) $sourceGroup->status->value,
            ]);

            $sectionMap = $this->buildSectionMap(
                sourceGroup: $sourceGroup,
                newGroup: $newGroup,
                targetCourseId: $targetCourseId,
                isCrossCourseClone: $isCrossCourseClone,
                copyCurriculumSections: $copyCurriculumSections,
            );

            if ($copySessions) {
                $this->cloneSessions($sourceGroup, $newGroup, $sectionMap, $isCrossCourseClone);
            }

            if ($copyLessons) {
                $this->cloneLessons($sourceCourseId, $targetCourseId, $sectionMap);
            }

            if ($copyAttachments) {
                $this->cloneAttachments($sourceCourseId, $targetCourseId, $sectionMap);
            }

            return $newGroup;
        });
    }

    private function resolveTitleValue(array $data, CourseGroup $sourceGroup, string $locale): ?string
    {
        $value = $data['title_'.$locale] ?? null;

        if (filled($value)) {
            return $value;
        }

        return $sourceGroup->getTranslation('title', $locale);
    }

    /**
     * @return array<int, int>
     */
    private function buildSectionMap(
        CourseGroup $sourceGroup,
        CourseGroup $newGroup,
        int $targetCourseId,
        bool $isCrossCourseClone,
        bool $copyCurriculumSections
    ): array {
        $sourceSections = CourseCurriculumSection::query()
            ->where('course_id', $sourceGroup->course_id)
            ->where('course_group_id', $sourceGroup->id)
            ->orderBy('sort_order')
            ->get();

        if (! $isCrossCourseClone && ! $copyCurriculumSections) {
            return $sourceSections
                ->mapWithKeys(fn (CourseCurriculumSection $section): array => [$section->id => $section->id])
                ->all();
        }

        if (! $copyCurriculumSections) {
            return [];
        }

        $sectionMap = [];
        foreach ($sourceSections as $sourceSection) {
            $newSection = CourseCurriculumSection::query()->create([
                'course_id' => $targetCourseId,
                'course_group_id' => $newGroup->id,
                'title' => $sourceSection->title,
                'description' => $sourceSection->description,
                'sort_order' => $sourceSection->sort_order,
            ]);

            $sectionMap[$sourceSection->id] = $newSection->id;
        }

        return $sectionMap;
    }

    /**
     * @param  array<int, int>  $sectionMap
     */
    private function cloneSessions(
        CourseGroup $sourceGroup,
        CourseGroup $newGroup,
        array $sectionMap,
        bool $isCrossCourseClone
    ): void {
        $sessions = CourseGroupSession::query()
            ->where('course_group_id', $sourceGroup->id)
            ->get();

        foreach ($sessions as $session) {
            CourseGroupSession::query()->create([
                'course_group_id' => $newGroup->id,
                'course_curriculum_section_id' => $this->mapSectionId($session->course_curriculum_section_id, $sectionMap, $isCrossCourseClone),
                'zoom_host_user_id' => $session->zoom_host_user_id,
                'zoom_host_email' => $session->zoom_host_email,
                'title' => $session->title,
                'content' => $session->content,
                'start_at' => $session->start_at,
                'duration_minutes' => $session->duration_minutes,
                'join_url' => $session->join_url,
                'recording_url' => $session->recording_url,
                'zoom_meeting_id' => $session->zoom_meeting_id,
                'zoom_meeting_uuid' => $session->zoom_meeting_uuid,
                'zoom_start_url' => $session->zoom_start_url,
                'zoom_synced_at' => $session->zoom_synced_at,
                'attachments' => $session->attachments,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $sectionMap
     */
    private function cloneLessons(int $sourceCourseId, int $targetCourseId, array $sectionMap): void
    {
        $lessons = Lesson::query()
            ->where('course_id', $sourceCourseId)
            ->get();

        foreach ($lessons as $lesson) {
            Lesson::query()->create([
                'course_id' => $targetCourseId,
                'course_curriculum_section_id' => $this->mapSectionId($lesson->course_curriculum_section_id, $sectionMap, $sourceCourseId !== $targetCourseId),
                'title' => $lesson->title,
                'content' => $lesson->content,
                'type' => $lesson->type,
                'pdf_path' => $lesson->pdf_path,
                'video_url' => $lesson->video_url,
                'is_preview' => $lesson->is_preview,
                'sort_order' => $lesson->sort_order,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $sectionMap
     */
    private function cloneAttachments(int $sourceCourseId, int $targetCourseId, array $sectionMap): void
    {
        $attachments = CourseAttachment::query()
            ->where('course_id', $sourceCourseId)
            ->get();

        foreach ($attachments as $attachment) {
            CourseAttachment::query()->create([
                'course_id' => $targetCourseId,
                'course_curriculum_section_id' => $this->mapSectionId($attachment->course_curriculum_section_id, $sectionMap, $sourceCourseId !== $targetCourseId),
                'title' => $attachment->title,
                'file_path' => $attachment->file_path,
                'visibility' => $attachment->visibility,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $sectionMap
     */
    private function mapSectionId(?int $sourceSectionId, array $sectionMap, bool $isCrossCourseClone): ?int
    {
        if (! $sourceSectionId) {
            return null;
        }

        if (isset($sectionMap[$sourceSectionId])) {
            return $sectionMap[$sourceSectionId];
        }

        return $isCrossCourseClone ? null : $sourceSectionId;
    }
}
