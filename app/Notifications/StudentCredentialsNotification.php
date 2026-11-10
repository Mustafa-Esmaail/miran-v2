<?php

namespace App\Notifications;

use App\Services\Settings\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ?string $plainPassword = null,
        private readonly bool $isNewAccount = true,
        private readonly ?string $courseTitle = null,
        private readonly ?string $courseImageUrl = null,
        private readonly ?string $enrollmentDate = null,
        private readonly ?string $invitedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(SettingsService::class);

        $isInviteOnly = $this->plainPassword === null;

        if ($isInviteOnly) {
            $statusLine = __('web.student_credentials.invited_existing');
        } elseif ($this->isNewAccount) {
            $statusLine = __('web.student_credentials.new_account');
        } else {
            $statusLine = __('web.student_credentials.password_updated');
        }

        return (new MailMessage)
            ->subject(__('web.student_credentials.subject'))
            ->markdown('mail.student-credentials', [
                'greeting' => __('web.student_credentials.greeting', ['name' => $notifiable->name]),
                'statusLine' => $statusLine,
                'isNewAccount' => $this->isNewAccount,
                'plainPassword' => $this->plainPassword,
                'emailLine' => __('web.student_credentials.email', ['email' => $notifiable->email]),
                'passwordLine' => $this->plainPassword !== null
                    ? __('web.student_credentials.password', ['password' => $this->plainPassword])
                    : null,
                'invitedByLine' => $this->invitedBy !== null
                    ? __('web.student_credentials.invited_by', ['name' => $this->invitedBy])
                    : null,
                'courseTitleLabel' => __('web.student_credentials.course_title'),
                'courseTitle' => $this->courseTitle,
                'enrollmentDateLabel' => __('web.student_credentials.enrollment_date'),
                'enrollmentDate' => $this->enrollmentDate ?? now()->format('Y-m-d H:i'),
                'courseImageUrl' => $this->courseImageUrl,
                'loginHint' => __('web.student_credentials.login_hint'),
                'securityNote' => $this->plainPassword !== null ? __('web.student_credentials.security_note') : null,
                'loginActionText' => __('web.student_credentials.login_action'),
                'loginActionUrl' => route('login'),
                'trainingVideoLabel' => __('web.student_credentials.training_video_label'),
                'trainingVideoDescription' => __('web.student_credentials.training_video_description'),
                'trainingVideoActionText' => __('web.student_credentials.training_video_action'),
                'trainingVideoUrl' => $settings->getValue('mail.training_video_url'),
                'certificateFormLabel' => __('web.student_credentials.certificate_form_label'),
                'certificateFormDescription' => __('web.student_credentials.certificate_form_description'),
                'certificateFormActionText' => __('web.student_credentials.certificate_form_action'),
                'certificateFormUrl' => $settings->getValue('mail.certificate_form_url'),
                'salutation' => __('web.mail.salutation', ['app' => config('app.name')]),
                'siteLogoUrl' => $settings->logoUrl(),
                'appName' => config('app.name'),
            ]);
    }
}
