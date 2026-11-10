<?php

namespace Tests\Unit;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ZoomServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_meeting_updates_session_zoom_fields(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'zoom-access-token',
            ], 200),
            'https://api.zoom.us/v2/users/*/meetings' => Http::response([
                'id' => '987654321',
                'uuid' => 'meeting-uuid',
                'join_url' => 'https://zoom.example/join/987654321',
                'start_url' => 'https://zoom.example/start/987654321',
            ], 201),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $session = $this->createSession($trainer);
        $session->update(['zoom_host_email' => 'trainer@example.com']);

        $payload = app(ZoomService::class)->createMeeting($session);

        $session->refresh();

        $this->assertSame('987654321', $session->zoom_meeting_id);
        $this->assertSame('meeting-uuid', $session->zoom_meeting_uuid);
        $this->assertSame('https://zoom.example/join/987654321', $session->join_url);
        $this->assertSame('https://zoom.example/start/987654321', $session->zoom_start_url);
        $this->assertSame('987654321', (string) ($payload['id'] ?? ''));
    }

    public function test_sync_session_attendance_persists_zoom_participants_and_matches_enrollment(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'zoom-access-token',
            ], 200),
            'https://api.zoom.us/v2/past_meetings/*/instances' => Http::response(['meetings' => []], 200),
            'https://api.zoom.us/v2/past_meetings/*/participants*' => Http::response([
                'participants' => [
                    [
                        'name' => 'Student One',
                        'user_email' => 'student1@example.com',
                        'join_time' => '2026-04-08T10:00:00Z',
                        'leave_time' => '2026-04-08T11:00:00Z',
                        'duration' => 60,
                    ],
                    [
                        'name' => 'Guest User',
                        'user_email' => 'guest@example.com',
                        'join_time' => '2026-04-08T10:10:00Z',
                        'leave_time' => '2026-04-08T10:40:00Z',
                        'duration' => 30,
                    ],
                ],
            ], 200),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $student = User::factory()->create(['email' => 'student1@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => \App\Enums\EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        app(ZoomService::class)->syncSessionAttendance($session);

        $session->refresh();
        $attendances = $session->zoomAttendances()->orderBy('participant_email')->get();

        $this->assertCount(2, $attendances);
        $this->assertSame($student->id, $attendances[1]->user_id);
        $this->assertSame($enrollment->id, $attendances[1]->enrollment_id);
        $this->assertSame('student1@example.com', $attendances[1]->participant_email);
        $this->assertSame(60, $attendances[1]->duration_minutes);
        $this->assertNotNull($session->zoom_synced_at);
    }

    public function test_sync_session_attendance_merges_participants_across_meeting_instances(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'oauth/token')) {
                return Http::response(['access_token' => 'zoom-access-token'], 200);
            }

            if (str_contains($url, '/past_meetings/987654321/instances')) {
                return Http::response([
                    'meetings' => [
                        ['uuid' => 'instance-uuid-1'],
                        ['uuid' => 'instance-uuid-2'],
                    ],
                ], 200);
            }

            if (str_contains($url, '/past_meetings/instance-uuid-1/participants')) {
                return Http::response([
                    'participants' => [
                        [
                            'name' => 'Student One',
                            'user_email' => 'student1@example.com',
                            'join_time' => '2026-04-08T10:00:00Z',
                            'leave_time' => '2026-04-08T10:30:00Z',
                            'duration' => 30,
                        ],
                        [
                            'name' => 'Guest User',
                            'user_email' => 'guest@example.com',
                            'join_time' => '2026-04-08T10:00:00Z',
                            'leave_time' => '2026-04-08T10:20:00Z',
                            'duration' => 20,
                        ],
                    ],
                ], 200);
            }

            if (str_contains($url, '/past_meetings/instance-uuid-2/participants')) {
                return Http::response([
                    'participants' => [
                        [
                            'name' => 'Student One',
                            'user_email' => 'student1@example.com',
                            'join_time' => '2026-04-08T10:35:00Z',
                            'leave_time' => '2026-04-08T11:05:00Z',
                            'duration' => 30,
                        ],
                    ],
                ], 200);
            }

            return Http::response([], 200);
        });

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $student = User::factory()->create(['email' => 'student1@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'instance-uuid-2',
        ]);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => \App\Enums\EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        app(ZoomService::class)->syncSessionAttendance($session);

        $attendances = $session->zoomAttendances()->orderBy('participant_email')->get();

        $this->assertCount(2, $attendances);

        $guest = $attendances->firstWhere('participant_email', 'guest@example.com');
        $this->assertSame(20, $guest->duration_minutes);
        $this->assertCount(1, $guest->raw_payload);

        $studentAttendance = $attendances->firstWhere('participant_email', 'student1@example.com');
        $this->assertSame($student->id, $studentAttendance->user_id);
        $this->assertSame(60, $studentAttendance->duration_minutes);
        $this->assertSame('2026-04-08 13:00:00', $studentAttendance->joined_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-08 14:05:00', $studentAttendance->left_at->format('Y-m-d H:i:s'));
        $this->assertCount(2, $studentAttendance->raw_payload);
    }

    private function mockZoomSettings(): void
    {
        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('zoomIsReady')->andReturn(true);
        $settings->shouldReceive('zoomConfig')->andReturn([
            'account_id' => 'account-id',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'webhook_secret' => 'webhook-secret',
        ]);

        $this->app->instance(SettingsService::class, $settings);
    }

    private function createSession(User $trainer): CourseGroupSession
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
            'title' => 'Zoom Session',
            'start_at' => now()->addHour(),
            'duration_minutes' => 60,
        ]);
    }
}
