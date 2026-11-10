<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SessionStartingNotification extends Notification
{
    public function __construct(private readonly string $title, private readonly string $startAt) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'start_at' => $this->startAt,
        ];
    }
}
