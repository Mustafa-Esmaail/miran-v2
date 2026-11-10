<?php

namespace App\Notifications;

use App\Models\CourseGroup;
use Illuminate\Notifications\Notification;

class CourseStartReminderNotification extends Notification
{
    public function __construct(private readonly CourseGroup $group) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $course = $this->group->course;

        return [
            'course_id' => $course?->id,
            'course_title' => $course?->getTranslation('title', app()->getLocale()),
            'course_group_id' => $this->group->id,
            'starts_at' => $this->group->start_at?->format('Y-m-d H:i'),
        ];
    }
}
