<?php

namespace App\Jobs;

use App\Mail\AdminBroadcastMail;
use App\Models\BroadcastMailRecipient;
use App\Services\BroadcastMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAdminBroadcastMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $recipientId,
    ) {}

    public function handle(BroadcastMailService $service): void
    {
        $recipient = BroadcastMailRecipient::query()->with(['broadcastMail', 'user'])->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        if (! $recipient->user || blank($recipient->user->email)) {
            $service->recordDelivery($recipient, false, 'Recipient or email missing.');

            return;
        }

        try {
            Mail::to($recipient->user->email)->send(new AdminBroadcastMail(
                $recipient->broadcastMail->subject,
                $recipient->broadcastMail->body,
            ));

            $service->recordDelivery($recipient, true);
        } catch (Throwable $exception) {
            $service->recordDelivery($recipient, false, $exception->getMessage());
        }
    }
}
