<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ZoomWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_validation_returns_encrypted_token(): void
    {
        $this->mockZoomSettings();

        $response = $this->postJson('/api/webhooks/zoom', [
            'event' => 'endpoint.url_validation',
            'payload' => [
                'plainToken' => 'plain-token',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'plainToken' => 'plain-token',
                'encryptedToken' => hash_hmac('sha256', 'plain-token', 'webhook-secret'),
            ]);
    }

    public function test_recording_completed_webhook_syncs_recording_and_attendance(): void
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
                        'leave_time' => '2026-04-08T11:15:00Z',
                        'duration' => 75,
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

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => \App\Enums\EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        $response = $this->postJson('/api/webhooks/zoom', [
            'event' => 'recording.completed',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'share_url' => 'https://zoom.example/recording/987654321',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson(['received' => true]);

        $session->refresh();

        $this->assertSame('https://zoom.example/recording/987654321', $session->recording_url);
        $this->assertCount(1, $session->zoomAttendances);
        $this->assertSame('student1@example.com', $session->zoomAttendances->first()->participant_email);
    }

    public function test_participant_joined_and_left_webhooks_track_live_attendance(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'zoom-access-token'], 200),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $student = User::factory()->create(['email' => 'student1@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
            'zoom_host_email' => 'trainer@example.com',
        ]);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $session->courseGroup->course_id,
            'course_group_id' => $session->course_group_id,
            'status' => \App\Enums\EnrollmentStatus::Active,
            'activated_at' => now(),
            'is_invited' => false,
        ]);

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.participant_joined',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'participant' => [
                        'id' => 'zoom-participant-host',
                        'email' => 'trainer@example.com',
                        'user_name' => 'Trainer Name',
                        'join_time' => '2026-04-08T10:05:00Z',
                    ],
                ],
            ],
        ])->assertOk();

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.participant_joined',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'participant' => [
                        'id' => 'zoom-participant-student',
                        'email' => 'student1@example.com',
                        'user_name' => 'Student One',
                        'join_time' => '2026-04-08T10:10:00Z',
                    ],
                ],
            ],
        ])->assertOk();

        $session->refresh();
        $liveRows = $session->liveAttendances()->get();
        $this->assertCount(2, $liveRows);

        $hostRow = $liveRows->firstWhere('participant_email', 'trainer@example.com');
        $this->assertSame('host', $hostRow->role);
        $this->assertSame('joined', $hostRow->status);
        $this->assertNull($hostRow->left_at);

        $studentRow = $liveRows->firstWhere('participant_email', 'student1@example.com');
        $this->assertSame('attendee', $studentRow->role);
        $this->assertSame('joined', $studentRow->status);
        $this->assertSame($student->id, $studentRow->user_id);
        $this->assertNull($studentRow->left_at);

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.participant_left',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'participant' => [
                        'id' => 'zoom-participant-student',
                        'email' => 'student1@example.com',
                        'leave_time' => '2026-04-08T10:40:00Z',
                    ],
                ],
            ],
        ])->assertOk();

        $studentRow->refresh();
        $hostRow->refresh();
        $this->assertNotNull($studentRow->left_at);
        $this->assertSame('2026-04-08 13:40:00', $studentRow->left_at->format('Y-m-d H:i:s'));
        $this->assertNull($hostRow->left_at);
    }

    public function test_meeting_started_webhook_records_actual_start_time(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'zoom-access-token'], 200),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
        ]);

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.started',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'start_time' => '2026-04-08T10:03:00Z',
                ],
            ],
        ])->assertOk();

        $session->refresh();
        $this->assertNotNull($session->zoom_actual_started_at);
        $this->assertSame('2026-04-08 13:03:00', $session->zoom_actual_started_at->format('Y-m-d H:i:s'));
    }

    public function test_meeting_ended_webhook_records_actual_end_time(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'zoom-access-token'], 200),
            'https://api.zoom.us/v2/past_meetings/*/instances' => Http::response(['meetings' => []], 200),
            'https://api.zoom.us/v2/past_meetings/*/participants*' => Http::response(['participants' => []], 200),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
        ]);

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.ended',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'end_time' => '2026-04-08T11:20:00Z',
                ],
            ],
        ])->assertOk();

        $session->refresh();
        $this->assertNotNull($session->zoom_actual_ended_at);
        $this->assertSame('2026-04-08 14:20:00', $session->zoom_actual_ended_at->format('Y-m-d H:i:s'));
    }

    public function test_participant_jbh_joined_and_waiting_room_webhooks_are_tracked_with_distinct_status(): void
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'zoom-access-token'], 200),
        ]);

        $this->mockZoomSettings();

        $trainer = User::factory()->create(['email' => 'trainer@example.com']);
        $session = $this->createSession($trainer);
        $session->update([
            'zoom_meeting_id' => '987654321',
            'zoom_meeting_uuid' => 'meeting-uuid',
            'zoom_host_email' => 'trainer@example.com',
        ]);

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.participant_jbh_joined',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'participant' => [
                        'id' => 'zoom-participant-early',
                        'email' => 'early@example.com',
                        'user_name' => 'Early Bird',
                        'join_time' => '2026-04-08T09:50:00Z',
                    ],
                ],
            ],
        ])->assertOk();

        $this->postJson('/api/webhooks/zoom', [
            'event' => 'meeting.participant_joined_waiting_room',
            'payload' => [
                'object' => [
                    'id' => '987654321',
                    'uuid' => 'meeting-uuid',
                    'participant' => [
                        'id' => 'zoom-participant-waiting',
                        'email' => 'waiting@example.com',
                        'user_name' => 'Waiting Student',
                        'join_time' => '2026-04-08T09:55:00Z',
                    ],
                ],
            ],
        ])->assertOk();

        $session->refresh();
        $liveRows = $session->liveAttendances()->get();
        $this->assertCount(2, $liveRows);

        $earlyRow = $liveRows->firstWhere('participant_email', 'early@example.com');
        $this->assertSame('joined_before_host', $earlyRow->status);
        $this->assertNull($earlyRow->left_at);

        $waitingRow = $liveRows->firstWhere('participant_email', 'waiting@example.com');
        $this->assertSame('waiting_room', $waitingRow->status);
        $this->assertNull($waitingRow->left_at);
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
