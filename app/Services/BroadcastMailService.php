<?php

namespace App\Services;

use App\Jobs\SendAdminBroadcastMailJob;
use App\Models\BroadcastMail;
use App\Models\BroadcastMailRecipient;
use Illuminate\Support\Facades\DB;

class BroadcastMailService
{
    /**
     * @param  array{subject: string, body: string, user_ids: array<int, int|string>}  $data
     */
    public function send(array $data, ?int $createdById): BroadcastMail
    {
        return DB::transaction(function () use ($data, $createdById): BroadcastMail {
            $broadcastMail = BroadcastMail::query()->create([
                'subject' => $data['subject'],
                'body' => $data['body'],
                'status' => BroadcastMail::STATUS_SENDING,
                'created_by' => $createdById,
            ]);

            foreach ($data['user_ids'] as $userId) {
                $recipient = $broadcastMail->recipients()->create([
                    'user_id' => (int) $userId,
                    'status' => BroadcastMailRecipient::STATUS_PENDING,
                ]);

                SendAdminBroadcastMailJob::dispatch($recipient->id);
            }

            return $broadcastMail;
        });
    }

    public function recordDelivery(BroadcastMailRecipient $recipient, bool $success, ?string $errorMessage = null): void
    {
        $recipient->update($success
            ? ['status' => BroadcastMailRecipient::STATUS_SENT, 'sent_at' => now()]
            : ['status' => BroadcastMailRecipient::STATUS_FAILED, 'error_message' => $errorMessage]);

        $this->refreshMailStatus($recipient->broadcastMail ?? $recipient->broadcastMail()->first());
    }

    private function refreshMailStatus(?BroadcastMail $broadcastMail): void
    {
        if (! $broadcastMail) {
            return;
        }

        $statuses = $broadcastMail->recipients()->pluck('status');

        $status = match (true) {
            $statuses->contains(BroadcastMailRecipient::STATUS_PENDING) => BroadcastMail::STATUS_SENDING,
            $statuses->every(fn (string $s): bool => $s === BroadcastMailRecipient::STATUS_SENT) => BroadcastMail::STATUS_SENT,
            $statuses->every(fn (string $s): bool => $s === BroadcastMailRecipient::STATUS_FAILED) => BroadcastMail::STATUS_FAILED,
            default => BroadcastMail::STATUS_PARTIALLY_FAILED,
        };

        $broadcastMail->update(['status' => $status]);
    }
}
