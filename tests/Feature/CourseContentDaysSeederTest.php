<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use Database\Seeders\CourseContentDaysSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseContentDaysSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_content_days_seeder_reassigns_existing_course_content_into_three_days(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'days_count' => 1,
        ]);

        $oldSection = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Old Section'],
            'sort_order' => 10,
        ]);

        foreach (range(1, 5) as $index) {
            Lesson::query()->create([
                'course_id' => $course->id,
                'course_curriculum_section_id' => $oldSection->id,
                'title' => ['en' => 'Lesson '.$index],
                'content' => ['en' => 'Lesson content '.$index],
                'type' => 'text',
                'sort_order' => $index,
            ]);
        }

        foreach (range(1, 4) as $index) {
            CourseAttachment::query()->create([
                'course_id' => $course->id,
                'course_curriculum_section_id' => $oldSection->id,
                'title' => ['en' => 'Attachment '.$index],
                'file_path' => 'attachments/file-'.$index.'.pdf',
                'visibility' => 'enrolled_only',
            ]);
        }

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
            'start_at' => Carbon::parse('2026-03-10 18:00:00'),
        ]);

        foreach (range(1, 4) as $index) {
            CourseGroupSession::query()->create([
                'course_group_id' => $group->id,
                'course_curriculum_section_id' => $oldSection->id,
                'title' => 'Session '.$index,
                'start_at' => Carbon::parse('2026-03-10 18:00:00')->addDays($index),
                'duration_minutes' => 90,
            ]);
        }

        $this->seed(CourseContentDaysSeeder::class);

        $course->refresh()->load([
            'curriculumSections',
            'lessons',
            'attachments',
            'groups.sessions',
        ]);

        $sections = $course->curriculumSections->sortBy('sort_order')->values();

        $this->assertSame(3, $course->days_count);
        $this->assertCount(3, $sections);
        $this->assertSame('Day 1', $sections[0]->getTranslation('title', 'en'));
        $this->assertSame('Day 2', $sections[1]->getTranslation('title', 'en'));
        $this->assertSame('Day 3', $sections[2]->getTranslation('title', 'en'));
        $this->assertFalse(CourseCurriculumSection::query()->whereKey($oldSection->id)->exists());
        $this->assertCount(5, $course->lessons->whereNotNull('course_curriculum_section_id'));
        $this->assertCount(4, $course->attachments->whereNotNull('course_curriculum_section_id'));
        $this->assertCount(4, $course->groups->flatMap->sessions->whereNotNull('course_curriculum_section_id'));
    }
}
