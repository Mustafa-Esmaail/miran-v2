<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentQuizSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $student,
        private readonly Quiz $quiz,
        private readonly string $courseTitle,
        private readonly int $score,
        private readonly int $totalPoints,
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
            'quiz_id' => $this->quiz->id,
            'quiz_title' => $this->quiz->title,
            'course_title' => $this->courseTitle,
            'score' => $this->score,
            'total_points' => $this->totalPoints,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student Quiz Submission: '.$this->quiz->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->student->name.' submitted the quiz "'.$this->quiz->title.'" in "'.$this->courseTitle.'".')
            ->line('Score: '.$this->score.' / '.$this->totalPoints);
    }
}
