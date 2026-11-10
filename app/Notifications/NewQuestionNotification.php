<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Course $course,
        private readonly CourseQuestion $question
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
            'course_id' => $this->course->id,
            'course_title' => $this->course->getTranslation('title', app()->getLocale()),
            'question_id' => $this->question->id,
            'question_title' => $this->question->title,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseTitle = $this->course->getTranslation('title', 'en');
        $asker = $this->question->user;

        return (new MailMessage)
            ->subject('New question in your course: '.$courseTitle)
            ->greeting('Hello '.$notifiable->name.',')
            ->line(($asker?->name ?? 'A student').' asked a question in "'.$courseTitle.'".')
            ->line('Question: '.$this->question->title);
    }
}
