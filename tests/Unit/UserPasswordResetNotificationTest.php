<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\UserPasswordResetNotification;
use Tests\TestCase;

class UserPasswordResetNotificationTest extends TestCase
{
    public function test_it_builds_reset_password_mail_message(): void
    {
        $user = User::factory()->make([
            'name' => 'Student Name',
            'email' => 'student@example.com',
        ]);

        $notification = new UserPasswordResetNotification('reset-token');
        $mail = $notification->toMail($user);

        $this->assertSame(__('web.mail.reset_password.subject'), $mail->subject);
        $this->assertSame(__('web.mail.reset_password.greeting', ['name' => 'Student Name']), $mail->greeting);
        $this->assertContains(__('web.mail.reset_password.intro'), $mail->introLines);
        $this->assertContains(__('web.mail.reset_password.outro'), $mail->outroLines);
        $this->assertSame(__('web.mail.reset_password.action'), $mail->actionText);
    }
}
