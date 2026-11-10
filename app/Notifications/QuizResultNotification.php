<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class QuizResultNotification extends Notification
{
    public function __construct(private readonly string $title, private readonly int $score) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'score' => $this->score];
    }
}
