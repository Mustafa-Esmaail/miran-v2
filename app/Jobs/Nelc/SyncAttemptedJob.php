<?php

namespace App\Jobs\Nelc;

use App\Models\QuizAttempt;
use App\Services\Nelc\NelcSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAttemptedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $quizAttemptId,
    ) {}

    public function handle(NelcSyncService $service): void
    {
        $attempt = QuizAttempt::query()->find($this->quizAttemptId);

        if (! $attempt) {
            return;
        }

        $service->syncAttempted($attempt);
    }
}
