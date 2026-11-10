<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Services\AnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnnouncementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_announcements_and_notifies_users(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $announcement = Announcement::factory()->create([
            'status' => 'draft',
            'starts_at' => null,
        ]);

        $service = $this->app->make(AnnouncementService::class);
        $service->publish($announcement);

        $this->assertSame('published', $announcement->fresh()->status);
        $this->assertNotNull($announcement->fresh()->starts_at);

        Notification::assertSentTo($user, AnnouncementNotification::class);
    }
}
