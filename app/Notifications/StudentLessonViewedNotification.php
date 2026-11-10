<?php

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentLessonViewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $student,
        private readonly Lesson $lesson,
        private readonly string $courseTitle,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User) {
            $preference = $notifiable->notificationPreference;
            if ($preference?->email_enabled ?? true) {
                $channels[] = 'mail';
            }
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->getTranslation('title', 'en'),
            'course_title' => $this->courseTitle,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student completed a lesson: '.$this->lesson->getTranslation('title', 'en'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->student->name.' completed the lesson "'.$this->lesson->getTranslation('title', 'en').'" in "'.$this->courseTitle.'".');
    }
}
