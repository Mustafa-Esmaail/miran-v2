<?php

namespace Tests\Unit;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\StudentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_student_with_active_enrollment(): void
    {
        Role::findOrCreate('student', 'web');

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
            'status' => CourseStatus::Published,
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'status' => CourseGroupStatus::Open,
        ]);

        $student = app(StudentCreationService::class)->create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => 'secret-password',
        ], $course->id, $group->id);

        $student->refresh();

        $this->assertTrue($student->hasRole('student'));
        $this->assertSame(User::STATUS_ACTIVE, $student->status);
        $this->assertTrue(
            Enrollment::query()
                ->where('user_id', $student->id)
                ->where('course_group_id', $group->id)
                ->where('status', EnrollmentStatus::Active)
                ->exists()
        );
    }
}
