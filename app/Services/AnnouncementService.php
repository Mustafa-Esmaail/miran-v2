<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Support\Carbon;

class AnnouncementService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function publish(Announcement $announcement, ?Carbon $when = null): Announcement
    {
        $announcement->forceFill([
            'status' => 'published',
            'starts_at' => $when ?? Carbon::now(),
        ])->save();

        $this->notifications->notifyAnnouncement($announcement);

        return $announcement;
    }

    public function unpublish(Announcement $announcement): Announcement
    {
        $announcement->forceFill(['status' => 'draft'])->save();

        return $announcement;
    }
}
