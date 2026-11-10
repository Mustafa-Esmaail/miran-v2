<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $plainPassword,
        private readonly string $adminLoginUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.mail.employee_credentials.subject', ['app' => config('app.name')]))
            ->greeting(__('admin.mail.employee_credentials.greeting', ['name' => $notifiable->name]))
            ->line(__('admin.mail.employee_credentials.intro'))
            ->line(__('admin.mail.employee_credentials.email_line', ['email' => $notifiable->email]))
            ->line(__('admin.mail.employee_credentials.password_line', ['password' => $this->plainPassword]))
            ->action(__('admin.mail.employee_credentials.login_action'), $this->adminLoginUrl)
            ->line(__('admin.mail.employee_credentials.security_note'));
    }
}
