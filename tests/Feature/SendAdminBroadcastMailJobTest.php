<?php

namespace Tests\Feature;

use App\Jobs\SendAdminBroadcastMailJob;
use App\Mail\AdminBroadcastMail;
use App\Models\BroadcastMail;
use App\Models\BroadcastMailRecipient;
use App\Models\User;
use App\Services\BroadcastMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendAdminBroadcastMailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_mail_and_marks_the_recipient_and_mail_as_sent(): void
    {
        $recipient = $this->makeRecipient(['email' => 'broadcast-target@example.test']);

        Mail::fake();

        (new SendAdminBroadcastMailJob($recipient->id))->handle(app(BroadcastMailService::class));

        Mail::assertQueued(
            AdminBroadcastMail::class,
            fn (AdminBroadcastMail $mail): bool => $mail->hasTo('broadcast-target@example.test')
        );

        $recipient->refresh();
        $this->assertSame(BroadcastMailRecipient::STATUS_SENT, $recipient->status);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(BroadcastMail::STATUS_SENT, $recipient->broadcastMail->fresh()->status);
    }

    public function test_job_marks_recipient_and_mail_as_failed_when_user_no_longer_exists(): void
    {
        $recipient = $this->makeRecipient();
        $recipient->user->delete();
        $recipient->refresh();

        Mail::fake();

        (new SendAdminBroadcastMailJob($recipient->id))->handle(app(BroadcastMailService::class));

        Mail::assertNothingSent();

        $recipient->refresh();
        $this->assertSame(BroadcastMailRecipient::STATUS_FAILED, $recipient->status);
        $this->assertSame(BroadcastMail::STATUS_FAILED, $recipient->broadcastMail->fresh()->status);
    }

    public function test_mail_status_is_partially_failed_when_only_some_recipients_succeed(): void
    {
        $broadcastMail = BroadcastMail::factory()->create(['status' => BroadcastMail::STATUS_SENDING]);
        $okRecipient = BroadcastMailRecipient::factory()->for($broadcastMail)->create([
            'user_id' => User::factory()->create(['email' => 'ok@example.test'])->id,
        ]);
        $badRecipient = BroadcastMailRecipient::factory()->for($broadcastMail)->create();
        $badRecipient->user->delete();
        $badRecipient->refresh();

        Mail::fake();

        $service = app(BroadcastMailService::class);
        (new SendAdminBroadcastMailJob($okRecipient->id))->handle($service);
        (new SendAdminBroadcastMailJob($badRecipient->id))->handle($service);

        $this->assertSame(BroadcastMail::STATUS_PARTIALLY_FAILED, $broadcastMail->fresh()->status);
    }

    private function makeRecipient(array $userAttributes = []): BroadcastMailRecipient
    {
        $broadcastMail = BroadcastMail::factory()->create(['status' => BroadcastMail::STATUS_SENDING]);
        $user = User::factory()->create($userAttributes);

        return BroadcastMailRecipient::factory()->for($broadcastMail)->create(['user_id' => $user->id]);
    }
}
