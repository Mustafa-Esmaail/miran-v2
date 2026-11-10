<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseInstructor;
use App\Models\User;
use App\Services\Admin\CourseGroupImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseGroupImportServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_group_and_generates_days_and_sessions_from_days_count_and_join_link(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $csv = implode("\n", [
            'course_id,group_title_ar,days_count,join_link',
            $course->id.',?????? ???????,3,https://example.com/join/abc',
        ]);

        $path = storage_path('app/testing-course-groups-import.csv');
        File::put($path, $csv);

        try {
            $summary = app(CourseGroupImportService::class)->import($path);
        } finally {
            File::delete($path);
        }

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, $summary['skipped']);

        $group = \App\Models\CourseGroup::query()->firstOrFail();

        $this->assertSame('?????? ???????', $group->getTranslation('title', 'ar'));
        $this->assertDatabaseCount('course_curriculum_sections', 3);
        $this->assertDatabaseCount('course_group_sessions', 3);

        $sections = \App\Models\CourseCurriculumSection::query()->orderBy('sort_order')->get();
        $this->assertSame('Day 1', $sections[0]->getTranslation('title', 'en'));
        $this->assertSame('Day 2', $sections[1]->getTranslation('title', 'en'));
        $this->assertSame($group->id, $sections[0]->course_group_id);
        $this->assertSame($group->id, $sections[1]->course_group_id);

        $sessions = \App\Models\CourseGroupSession::query()->orderBy('id')->get();
        $this->assertNotEmpty($sessions[0]->title);
        $this->assertNotEmpty($sessions[1]->title);
        $this->assertNotSame($sessions[0]->title, $sessions[1]->title);
        $this->assertSame('https://example.com/join/abc', $sessions[0]->join_url);
        $this->assertSame('https://example.com/join/abc', $sessions[1]->join_url);
        $this->assertSame($group->id, $sessions[0]->course_group_id);
    }

    #[Test]
    public function it_updates_existing_group_and_resyncs_days_when_id_column_is_provided(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $service = app(CourseGroupImportService::class);

        $createCsv = implode('
', [
            'course_id,group_title_en,capacity,days_count,join_link',
            $course->id.',Morning Group,30,2,https://example.com/join/old',
        ]);

        $createPath = storage_path('app/testing-course-groups-import-create.csv');
        File::put($createPath, $createCsv);

        try {
            $service->import($createPath);
        } finally {
            File::delete($createPath);
        }

        $group = \App\Models\CourseGroup::query()->firstOrFail();
        $this->assertSame(30, $group->capacity);

        $updateCsv = implode('
', [
            'id,course_id,capacity,days_count,join_link',
            $group->id.','.$course->id.',50,3,https://example.com/join/new',
        ]);

        $updatePath = storage_path('app/testing-course-groups-import-update.csv');
        File::put($updatePath, $updateCsv);

        try {
            $summary = $service->import($updatePath);
        } finally {
            File::delete($updatePath);
        }

        $this->assertSame(1, $summary['processed']);
        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['updated']);
        $this->assertSame(0, $summary['skipped']);

        $group->refresh();
        $this->assertSame(50, $group->capacity);
        $this->assertSame('Morning Group', $group->getTranslation('title', 'en'));

        $this->assertDatabaseCount('course_curriculum_sections', 3);
        $this->assertDatabaseCount('course_group_sessions', 3);

        $sessions = \App\Models\CourseGroupSession::query()->orderBy('id')->get();
        $this->assertSame('https://example.com/join/new', $sessions[0]->join_url);
        $this->assertSame('https://example.com/join/new', $sessions[2]->join_url);
    }

    #[Test]
    public function it_attaches_trainer_to_course_when_trainer_id_column_is_provided(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $instructorRole = Role::query()->firstOrCreate(['name' => 'instructor', 'guard_name' => 'admin']);
        $trainer = User::factory()->create();
        $trainer->assignRole($instructorRole);

        $csv = implode("\n", [
            'course_id,trainer_id,group_title_en,days_count,join_link',
            $course->id.','.$trainer->id.',Morning Group,2,https://example.com/join/abc',
        ]);

        $path = storage_path('app/testing-course-groups-import-trainer.csv');
        File::put($path, $csv);

        try {
            $summary = app(CourseGroupImportService::class)->import($path);
        } finally {
            File::delete($path);
        }

        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertTrue($course->trainers()->whereKey($trainer->id)->exists());
    }

    #[Test]
    public function it_skips_row_when_trainer_id_is_not_an_instructor(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $nonTrainer = User::factory()->create();

        $csv = implode("\n", [
            'course_id,trainer_id,group_title_en,days_count,join_link',
            $course->id.','.$nonTrainer->id.',Morning Group,2,https://example.com/join/abc',
        ]);

        $path = storage_path('app/testing-course-groups-import-invalid-trainer.csv');
        File::put($path, $csv);

        try {
            $summary = app(CourseGroupImportService::class)->import($path);
        } finally {
            File::delete($path);
        }

        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertFalse($course->trainers()->whereKey($nonTrainer->id)->exists());
    }

    #[Test]
    public function it_scopes_imported_trainer_to_the_specific_group_only(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        $instructorRole = Role::query()->firstOrCreate(['name' => 'instructor', 'guard_name' => 'admin']);
        $trainerA = User::factory()->create();
        $trainerA->assignRole($instructorRole);
        $trainerB = User::factory()->create();
        $trainerB->assignRole($instructorRole);

        $service = app(CourseGroupImportService::class);

        $csvA = implode("\n", [
            'course_id,trainer_id,group_title_en,days_count,join_link',
            $course->id.','.$trainerA->id.',Group A,2,https://example.com/join/a',
        ]);
        $pathA = storage_path('app/testing-course-groups-import-trainer-a.csv');
        File::put($pathA, $csvA);

        try {
            $service->import($pathA);
        } finally {
            File::delete($pathA);
        }

        $groupA = \App\Models\CourseGroup::query()->where('title->en', 'Group A')->firstOrFail();

        $csvB = implode("\n", [
            'course_id,trainer_id,group_title_en,days_count,join_link',
            $course->id.','.$trainerB->id.',Group B,2,https://example.com/join/b',
        ]);
        $pathB = storage_path('app/testing-course-groups-import-trainer-b.csv');
        File::put($pathB, $csvB);

        try {
            $service->import($pathB);
        } finally {
            File::delete($pathB);
        }

        $groupB = \App\Models\CourseGroup::query()->where('title->en', 'Group B')->firstOrFail();

        $this->assertTrue(CourseInstructor::query()->where([
            'course_id' => $course->id,
            'course_group_id' => $groupA->id,
            'user_id' => $trainerA->id,
        ])->exists());

        $this->assertTrue(CourseInstructor::query()->where([
            'course_id' => $course->id,
            'course_group_id' => $groupB->id,
            'user_id' => $trainerB->id,
        ])->exists());

        $this->assertFalse(CourseInstructor::query()->where([
            'course_id' => $course->id,
            'course_group_id' => $groupB->id,
            'user_id' => $trainerA->id,
        ])->exists());

        $this->assertFalse(CourseInstructor::query()->where([
            'course_id' => $course->id,
            'course_group_id' => $groupA->id,
            'user_id' => $trainerB->id,
        ])->exists());
    }
}
