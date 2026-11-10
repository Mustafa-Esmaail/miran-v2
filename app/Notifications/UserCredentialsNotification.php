<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $plainPassword,
        private readonly string $adminLoginUrl,
        private readonly bool $isNewAccount = true,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $introKey = $this->isNewAccount
            ? 'admin.mail.user_credentials.intro_new'
            : 'admin.mail.user_credentials.intro_update';

        return (new MailMessage)
            ->subject(__('admin.mail.user_credentials.subject', ['app' => config('app.name')]))
            ->greeting(__('admin.mail.user_credentials.greeting', ['name' => $notifiable->name]))
            ->line(__($introKey))
            ->line(__('admin.mail.user_credentials.email_line', ['email' => $notifiable->email]))
            ->line(__('admin.mail.user_credentials.password_line', ['password' => $this->plainPassword]))
            ->action(__('admin.mail.user_credentials.login_action'), $this->adminLoginUrl)
            ->line(__('admin.mail.user_credentials.security_note'));
    }
}
