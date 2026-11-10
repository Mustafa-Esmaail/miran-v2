<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Notifications\Notification;

class CourseUpdatedNotification extends Notification
{
    public function __construct(private readonly Course $course, private readonly array $changes = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'course_id' => $this->course->id,
            'course_title' => $this->course->getTranslation('title', app()->getLocale()),
            'changes' => $this->changes,
        ];
    }
}
