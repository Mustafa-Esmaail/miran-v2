<?php

namespace Database\Factories;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ZoomSessionAttendance>
 */
class ZoomSessionAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_group_session_id' => fn (): int => $this->createSession()->id,
            'user_id' => null,
            'enrollment_id' => null,
            'zoom_meeting_id' => (string) fake()->randomNumber(8, true),
            'participant_name' => fake()->name(),
            'participant_email' => fake()->safeEmail(),
            'joined_at' => now()->subHour(),
            'left_at' => now(),
            'duration_minutes' => 60,
            'raw_payload' => [],
        ];
    }

    private function createSession(): CourseGroupSession
    {
        $category = Category::query()->create([
            'name' => ['en' => fake()->word()],
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => fake()->sentence(2)],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => fake()->unique()->slug(),
            'status' => CourseStatus::Published,
        ]);

        $section = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Day 1'],
            'description' => ['en' => 'First day'],
            'sort_order' => 1,
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => fake()->sentence(2)],
            'status' => CourseGroupStatus::Open,
        ]);

        return CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $section->id,
            'title' => 'Zoom Session',
            'start_at' => now()->addHour(),
            'duration_minutes' => 60,
        ]);
    }
}
