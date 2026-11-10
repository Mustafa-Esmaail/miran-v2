<?php

namespace App\Jobs\Nelc;

use App\Models\CourseReview;
use App\Services\Nelc\NelcSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncRatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $courseReviewId,
    ) {}

    public function handle(NelcSyncService $service): void
    {
        $review = CourseReview::query()->find($this->courseReviewId);

        if (! $review) {
            return;
        }

        $service->syncRated($review);
    }
}
