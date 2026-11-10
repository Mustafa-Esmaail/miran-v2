<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class UserPasswordResetNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('web.mail.reset_password.subject'))
            ->greeting(__('web.mail.reset_password.greeting', ['name' => $notifiable->name]))
            ->line(__('web.mail.reset_password.intro'))
            ->action(__('web.mail.reset_password.action'), $this->resetUrl($notifiable))
            ->line(__('web.mail.reset_password.expiry', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('web.mail.reset_password.outro'));
    }
}
