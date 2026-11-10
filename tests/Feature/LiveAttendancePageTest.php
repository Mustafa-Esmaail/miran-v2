<?php

namespace Tests\Feature;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Pages\LiveAttendance;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\ZoomLiveAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveAttendancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_permission_to_access_the_page(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->assertFalse(LiveAttendance::canAccess());

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'zoom.view_live_attendance', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('zoom.view_live_attendance');

        $this->assertTrue(LiveAttendance::canAccess());
    }

    public function test_it_reports_instructor_lateness_and_students_not_yet_joined(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'zoom.view_live_attendance', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('zoom.view_live_attendance');

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $studentJoined = User::factory()->create(['email' => 'joined@example.com']);
        $studentMissing = User::factory()->create(['email' => 'missing@example.com']);
        $studentWaiting = User::factory()->create(['email' => 'waiting@example.com']);

        $session = $this->createLiveSession($trainer);

        Enrollment::query()->create([
            'user_id' => $studentJoined->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        $missingEnrollment = Enrollment::query()->create([
            'user_id' => $studentMissing->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        Enrollment::query()->create([
            'user_id' => $studentWaiting->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        $joinedEnrollment = Enrollment::query()->where('user_id', $studentJoined->id)->firstOrFail();
        $waitingEnrollment = Enrollment::query()->where('user_id', $studentWaiting->id)->firstOrFail();

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'participant_email' => 'trainer@example.com',
            'participant_name' => 'Trainer Name',
            'role' => 'host',
            'status' => 'joined',
            'joined_at' => $session->start_at->copy()->addMinutes(7),
            'left_at' => null,
        ]);

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'user_id' => $studentJoined->id,
            'enrollment_id' => $joinedEnrollment->id,
            'participant_email' => 'joined@example.com',
            'participant_name' => 'Joined Student',
            'role' => 'attendee',
            'status' => 'joined',
            'joined_at' => $session->start_at->copy()->addMinutes(1),
            'left_at' => null,
        ]);

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'user_id' => $studentWaiting->id,
            'enrollment_id' => $waitingEnrollment->id,
            'participant_email' => 'waiting@example.com',
            'participant_name' => 'Waiting Student',
            'role' => 'attendee',
            'status' => 'waiting_room',
            'joined_at' => $session->start_at->copy()->addMinutes(2),
            'left_at' => null,
        ]);

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'participant_email' => 'early@example.com',
            'participant_name' => 'Early Bird',
            'role' => 'attendee',
            'status' => 'joined_before_host',
            'joined_at' => $session->start_at->copy()->subMinutes(5),
            'left_at' => null,
        ]);

        $rows = Livewire::test(LiveAttendance::class)->assertOk()->instance()->getLiveSessions();

        $this->assertCount(1, $rows);
        $row = $rows->first();

        $this->assertSame('late', $row['instructor_status']['state']);
        $this->assertStringContainsString('7', $row['instructor_status']['label']);
        $this->assertSame(3, $row['enrolled_count']);
        $this->assertSame(1, $row['joined_count']);
        $this->assertSame(1, $row['waiting_count']);

        $notJoinedEmails = $row['not_joined']->pluck('email');
        $this->assertTrue($notJoinedEmails->contains('missing@example.com'));
        $this->assertFalse($notJoinedEmails->contains('joined@example.com'));
        $this->assertFalse($notJoinedEmails->contains('waiting@example.com'));

        $waitingEmails = $row['waiting']->pluck('email');
        $this->assertTrue($waitingEmails->contains('waiting@example.com'));

        $earlyEmails = $row['joined_before_host']->pluck('email');
        $this->assertTrue($earlyEmails->contains('early@example.com'));

        $this->assertNull($row['meeting_ended_at']);

        $session->update(['zoom_actual_ended_at' => now()]);
        $endedRow = Livewire::test(LiveAttendance::class)->instance()->getLiveSessions()->first();
        $this->assertNotNull($endedRow['meeting_ended_at']);

        $this->assertNotNull($missingEnrollment);
    }

    public function test_it_groups_rejoin_segments_and_exposes_recording_url(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'zoom.view_live_attendance', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('zoom.view_live_attendance');

        $trainer = User::factory()->create(['email' => 'trainer2@example.com']);
        $student = User::factory()->create(['email' => 'rejoiner@example.com']);

        $session = $this->createLiveSession($trainer);
        $session->update(['recording_url' => 'https://zoom.example/recording/rejoin-session']);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        $enrollment = Enrollment::query()->where('user_id', $student->id)->firstOrFail();

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'participant_email' => 'rejoiner@example.com',
            'participant_name' => 'Rejoining Student',
            'role' => 'attendee',
            'status' => 'joined',
            'joined_at' => $session->start_at->copy()->addMinutes(1),
            'left_at' => $session->start_at->copy()->addMinutes(10),
        ]);

        ZoomLiveAttendance::query()->create([
            'course_group_session_id' => $session->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'participant_email' => 'rejoiner@example.com',
            'participant_name' => 'Rejoining Student',
            'role' => 'attendee',
            'status' => 'joined',
            'joined_at' => $session->start_at->copy()->addMinutes(15),
            'left_at' => null,
        ]);

        $row = Livewire::test(LiveAttendance::class)->assertOk()->instance()->getLiveSessions()->first();

        $this->assertSame('https://zoom.example/recording/rejoin-session', $row['recording_url']);

        $attendee = $row['attendees']->firstWhere('email', 'rejoiner@example.com');
        $this->assertNotNull($attendee);
        $this->assertStringContainsString('rejoined', $attendee['meta']);
        $this->assertStringContainsString('still in meeting', $attendee['meta']);

        // Joined once, present in the joined count exactly once despite two segments.
        $this->assertSame(1, $row['joined_count']);
    }

    private function createLiveSession(User $trainer): CourseGroupSession
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
        $course->trainers()->attach($trainer->id);

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
            'title' => 'Live Session',
            'zoom_host_email' => $trainer->email,
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
            'start_at' => now()->subMinutes(20),
            'duration_minutes' => 60,
        ]);
    }
}
