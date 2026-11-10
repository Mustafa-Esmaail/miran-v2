<?php

namespace App\Notifications;

use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use Illuminate\Notifications\Notification;

class NewQuestionAnswerNotification extends Notification
{
    public function __construct(
        private readonly CourseQuestion $question,
        private readonly QuestionAnswer $answer
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $course = $this->question->course;

        return [
            'course_id' => $this->question->course_id,
            'question_id' => $this->question->id,
            'question_title' => $this->question->title,
            'answer_id' => $this->answer->id,
            'course_title' => $course?->getTranslation('title', app()->getLocale()),
        ];
    }
}
