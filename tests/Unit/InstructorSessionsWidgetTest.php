<?php

namespace Tests\Unit;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Filament\Widgets\InstructorSessionsWidget;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorSessionsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_sees_only_their_own_sessions(): void
    {
        $instructor = User::factory()->create();
        $otherInstructor = User::factory()->create();

        $instructorRole = Role::findOrCreate('instructor', 'admin');
        $instructor->assignRole($instructorRole);
        $otherInstructor->assignRole($instructorRole);

        $ownedSession = $this->createSessionForTrainer($instructor, 'Instructor Session');
        $otherSession = $this->createSessionForTrainer($otherInstructor, 'Other Session');

        $sessionIds = InstructorSessionsWidget::getScopedQueryForUser($instructor)
            ->pluck('id');

        $this->assertTrue($sessionIds->contains($ownedSession->id));
        $this->assertFalse($sessionIds->contains($otherSession->id));
    }

    public function test_admin_sees_all_sessions(): void
    {
        $admin = User::factory()->create();
        $trainerA = User::factory()->create();
        $trainerB = User::factory()->create();

        $adminRole = Role::findOrCreate('admin', 'admin');
        $instructorRole = Role::findOrCreate('instructor', 'admin');

        $admin->assignRole($adminRole);
        $trainerA->assignRole($instructorRole);
        $trainerB->assignRole($instructorRole);

        $sessionA = $this->createSessionForTrainer($trainerA, 'Session A');
        $sessionB = $this->createSessionForTrainer($trainerB, 'Session B');

        $sessionIds = InstructorSessionsWidget::getScopedQueryForUser($admin)
            ->pluck('id');

        $this->assertTrue($sessionIds->contains($sessionA->id));
        $this->assertTrue($sessionIds->contains($sessionB->id));
    }

    public function test_course_title_search_matches_related_course_title(): void
    {
        $trainer = User::factory()->create();
        $session = $this->createSessionForTrainer($trainer, 'Matching Session', 'Strategic Leadership');

        $sessionIds = InstructorSessionsWidget::applyCourseTitleSearch(
            CourseGroupSession::query(),
            'Strategic'
        )->pluck('id');

        $this->assertTrue($sessionIds->contains($session->id));
    }

    public function test_instructor_search_matches_related_trainer_name(): void
    {
        $trainer = User::factory()->create(['name' => 'Sara Trainer']);
        $session = $this->createSessionForTrainer($trainer, 'Trainer Session');

        $sessionIds = InstructorSessionsWidget::applyInstructorSearch(
            CourseGroupSession::query(),
            'Sara'
        )->pluck('id');

        $this->assertTrue($sessionIds->contains($session->id));
    }

    private function createSessionForTrainer(User $trainer, string $title, ?string $courseTitle = null): CourseGroupSession
    {
        $category = Category::query()->create([
            'name' => ['en' => fake()->word()],
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $courseTitle ?? fake()->sentence(2)],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => fake()->unique()->slug(),
            'status' => CourseStatus::Published,
        ]);

        $course->trainers()->attach($trainer->id);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => fake()->sentence(2)],
            'status' => CourseGroupStatus::Open,
        ]);

        return CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'title' => $title,
            'start_at' => now()->addHour(),
            'duration_minutes' => 60,
        ]);
    }
}
