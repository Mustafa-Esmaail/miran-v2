<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class RecordingAvailableNotification extends Notification
{
    public function __construct(private readonly string $title) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title];
    }
}
