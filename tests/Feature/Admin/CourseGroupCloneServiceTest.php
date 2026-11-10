<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use App\Services\Admin\CourseGroupCloneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CourseGroupCloneServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clones_group_and_related_content_to_another_course(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $sourceCourse = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Source Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'source-course',
            'status' => 'published',
        ]);

        $targetCourse = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Target Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'target-course',
            'status' => 'published',
        ]);

        $sourceGroup = CourseGroup::query()->create([
            'course_id' => $sourceCourse->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 1500,
            'status' => 'open',
        ]);

        $sourceSection = CourseCurriculumSection::query()->create([
            'course_id' => $sourceCourse->id,
            'course_group_id' => $sourceGroup->id,
            'title' => ['en' => 'Day 1'],
            'description' => ['en' => 'Day desc'],
            'sort_order' => 1,
        ]);

        CourseGroupSession::query()->create([
            'course_group_id' => $sourceGroup->id,
            'course_curriculum_section_id' => $sourceSection->id,
            'title' => 'Live Session',
            'start_at' => now()->addDay(),
            'duration_minutes' => 90,
            'join_url' => 'https://example.com/join',
        ]);

        Lesson::query()->create([
            'course_id' => $sourceCourse->id,
            'course_curriculum_section_id' => $sourceSection->id,
            'title' => ['en' => 'Lesson 1'],
            'content' => ['en' => 'Lesson content'],
            'type' => 'video',
            'video_url' => 'https://example.com/video',
            'is_preview' => false,
            'sort_order' => 1,
        ]);

        CourseAttachment::query()->create([
            'course_id' => $sourceCourse->id,
            'course_curriculum_section_id' => $sourceSection->id,
            'title' => ['en' => 'Attachment 1'],
            'file_path' => 'course-attachments/one.pdf',
            'visibility' => 'public',
        ]);

        $newGroup = app(CourseGroupCloneService::class)->clone($sourceGroup, [
            'course_id' => $targetCourse->id,
            'title_ar' => 'مجموعة منسوخة',
            'title_en' => 'Copied Group',
            'status' => 'open',
            'copy_curriculum_sections' => true,
            'copy_sessions' => true,
            'copy_lessons' => true,
            'copy_attachments' => true,
        ]);

        $this->assertDatabaseHas('course_groups', [
            'id' => $newGroup->id,
            'course_id' => $targetCourse->id,
        ]);

        $newSection = CourseCurriculumSection::query()
            ->where('course_id', $targetCourse->id)
            ->where('course_group_id', $newGroup->id)
            ->first();

        $this->assertNotNull($newSection);

        $this->assertDatabaseHas('course_group_sessions', [
            'course_group_id' => $newGroup->id,
            'course_curriculum_section_id' => $newSection->id,
            'title' => 'Live Session',
        ]);

        $this->assertDatabaseHas('lessons', [
            'course_id' => $targetCourse->id,
            'course_curriculum_section_id' => $newSection->id,
            'type' => 'video',
        ]);

        $this->assertDatabaseHas('course_attachments', [
            'course_id' => $targetCourse->id,
            'course_curriculum_section_id' => $newSection->id,
            'file_path' => 'course-attachments/one.pdf',
        ]);
    }

    public function test_it_requires_copying_curriculum_when_cloning_related_content_cross_course(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $sourceCourse = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Source Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'source-course',
            'status' => 'published',
        ]);

        $targetCourse = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Target Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'target-course',
            'status' => 'published',
        ]);

        $sourceGroup = CourseGroup::query()->create([
            'course_id' => $sourceCourse->id,
            'title' => ['en' => 'Group A'],
            'status' => 'open',
        ]);

        $this->expectException(ValidationException::class);

        app(CourseGroupCloneService::class)->clone($sourceGroup, [
            'course_id' => $targetCourse->id,
            'title_en' => 'Copied Group',
            'copy_curriculum_sections' => false,
            'copy_sessions' => true,
            'copy_lessons' => false,
            'copy_attachments' => false,
        ]);
    }
}
