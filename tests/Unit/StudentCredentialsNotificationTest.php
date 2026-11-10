<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\StudentCredentialsNotification;
use App\Services\Settings\SettingsService;
use Mockery;
use Tests\TestCase;

class StudentCredentialsNotificationTest extends TestCase
{
    public function test_it_builds_student_credentials_mail_message(): void
    {
        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('getValue')->with('mail.training_video_url')->andReturn('https://example.com/training-video');
        $settings->shouldReceive('getValue')->with('mail.certificate_form_url')->andReturn('https://example.com/certificate-form');
        $settings->shouldReceive('logoUrl')->andReturn('https://example.com/logo.png');
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->make([
            'name' => 'Student Name',
            'email' => 'student@example.com',
        ]);

        $notification = new StudentCredentialsNotification('Password123!', true);
        $mail = $notification->toMail($user);

        $this->assertSame(__('web.student_credentials.subject'), $mail->subject);
        $this->assertSame('mail.student-credentials', $mail->markdown);
        $this->assertSame(__('web.student_credentials.greeting', ['name' => 'Student Name']), $mail->viewData['greeting']);
        $this->assertSame(__('web.student_credentials.new_account'), $mail->viewData['statusLine']);
        $this->assertSame(__('web.student_credentials.email', ['email' => 'student@example.com']), $mail->viewData['emailLine']);
        $this->assertSame(__('web.student_credentials.password', ['password' => 'Password123!']), $mail->viewData['passwordLine']);
        $this->assertArrayHasKey('enrollmentDate', $mail->viewData);
        $this->assertNotEmpty($mail->viewData['enrollmentDate']);
        $this->assertSame('https://example.com/training-video', $mail->viewData['trainingVideoUrl']);
        $this->assertSame('https://example.com/certificate-form', $mail->viewData['certificateFormUrl']);
        $this->assertSame('https://example.com/logo.png', $mail->viewData['siteLogoUrl']);
        $this->assertSame(__('web.student_credentials.login_action'), $mail->viewData['loginActionText']);
        $this->assertSame(__('web.mail.salutation', ['app' => config('app.name')]), $mail->viewData['salutation']);
    }
}
