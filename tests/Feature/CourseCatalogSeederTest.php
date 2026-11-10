<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CourseCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_catalog_seeder_creates_days_and_assigns_content(): void
    {
        $this->seed(CourseCatalogSeeder::class);

        $course = Course::query()
            ->with(['curriculumSections.lessons', 'curriculumSections.attachments', 'groups.sessions'])
            ->firstOrFail();

        $this->assertCount(4, $course->curriculumSections);
        $this->assertSame('Day 1', $course->curriculumSections->sortBy('sort_order')->first()?->getTranslation('title', 'en'));
        $this->assertTrue($course->curriculumSections->contains(fn ($section) => $section->lessons->isNotEmpty()));
        $this->assertTrue($course->curriculumSections->contains(fn ($section) => $section->attachments->isNotEmpty()));
        $this->assertTrue($course->groups->flatMap->sessions->isNotEmpty());
        $this->assertTrue($course->lessons()->whereNotNull('course_curriculum_section_id')->exists());
    }
}
