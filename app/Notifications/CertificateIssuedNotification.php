<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CertificateIssuedNotification extends Notification
{
    public function __construct(private readonly string $courseTitle) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['course_title' => $this->courseTitle];
    }
}
