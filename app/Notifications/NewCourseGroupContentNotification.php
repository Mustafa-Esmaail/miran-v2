<?php

namespace App\Notifications;

use App\Models\CourseGroupSession;
use App\Models\User;
use App\Services\EmailLoginLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCourseGroupContentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CourseGroupSession $session) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $preference = $notifiable->notificationPreference;
        $databaseEnabled = $preference?->database_enabled ?? true;
        $emailEnabled = $preference?->email_enabled ?? true;
        $courseUpdatesEnabled = $preference?->course_updates_enabled ?? true;

        if (! $courseUpdatesEnabled) {
            return [];
        }

        $channels = [];

        if ($databaseEnabled) {
            $channels[] = 'database';
        }

        if ($emailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        $group = $this->session->courseGroup;
        $course = $group?->course;

        return [
            'course_id' => $course?->id,
            'course_title' => $course?->getTranslation('title', app()->getLocale()),
            'course_group_id' => $group?->id,
            'course_group_title' => $group?->getTranslation('title', app()->getLocale()),
            'session_id' => $this->session->id,
            'session_title' => $this->session->title,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $group = $this->session->courseGroup;
        $course = $group?->course;
        $courseTitle = $course?->getTranslation('title', app()->getLocale()) ?? __('web.mail.course_group_content.fallback_course');
        $groupTitle = $group?->getTranslation('title', app()->getLocale()) ?? __('web.mail.course_group_content.fallback_group');
        $sessionTitle = $this->session->title ?: __('web.mail.course_group_content.fallback_content');

        return (new MailMessage)
            ->subject(__('web.mail.course_group_content.subject'))
            ->greeting(__('web.mail.course_group_content.greeting', ['name' => $notifiable->name]))
            ->line(__('web.mail.course_group_content.intro'))
            ->line(__('web.mail.course_group_content.course', ['course' => $courseTitle]))
            ->line(__('web.mail.course_group_content.group', ['group' => $groupTitle]))
            ->line(__('web.mail.course_group_content.content', ['content' => $sessionTitle]))
            ->action(__('web.mail.course_group_content.action'), $this->actionUrl($notifiable))
            ->line(__('web.mail.course_group_content.login_line', ['url' => route('login')]));
    }

    private function actionUrl(object $notifiable): string
    {
        $group = $this->session->courseGroup;
        $course = $group?->course;

        $targetUrl = $course?->slug && $group
            ? route('dashboard.course-player', ['course_slug' => $course->slug, 'group' => $group->id])
            : route('dashboard.courses');

        if (! $notifiable instanceof User) {
            return $targetUrl;
        }

        return app(EmailLoginLinkService::class)->urlFor($notifiable, $targetUrl);
    }
}
