<?php

namespace App\Jobs\Nelc;

use App\Models\Enrollment;
use App\Services\Nelc\NelcSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInitializedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $enrollmentId,
    ) {}

    public function handle(NelcSyncService $service): void
    {
        $enrollment = Enrollment::query()->find($this->enrollmentId);

        if (! $enrollment) {
            return;
        }

        $service->syncInitialized($enrollment);
    }
}
