<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseQuestion;
use Illuminate\Notifications\Notification;

class NewCourseQuestionNotification extends Notification
{
    public function __construct(
        private readonly Course $course,
        private readonly CourseQuestion $question
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
