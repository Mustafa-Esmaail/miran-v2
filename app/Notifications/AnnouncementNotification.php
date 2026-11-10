<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    public function __construct(private readonly Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->getTranslation('title', app()->getLocale()),
            'body' => $this->announcement->getTranslation('body', app()->getLocale()),
        ];
    }
}
