<?php

namespace App\Notifications;

use App\Models\CourseGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EnrollmentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
        ];
    }
}
