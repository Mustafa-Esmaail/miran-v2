<?php

namespace App\Console\Commands;

use App\Enums\ServiceRequestSource;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;

class AutoFlagUncontactedComplaintsCommand extends Command
{
    private const NOT_CONTACTED_STATUS_SLUG = 'complaint_not_contacted';

    private const HOURS_THRESHOLD = 2;

    protected $signature = 'service-requests:flag-uncontacted-complaints';

    protected $description = 'Move complaint service requests still "New" 2+ hours after submission to "Not contacted"';

    public function handle(): int
    {
        $requests = ServiceRequest::query()
            ->where('source', ServiceRequestSource::Complaint)
            ->where('status', 'new')
            ->where('created_at', '<=', now()->subHours(self::HOURS_THRESHOLD))
            ->get();

        foreach ($requests as $request) {
            $request->update(['status' => self::NOT_CONTACTED_STATUS_SLUG]);
        }

        $this->info("{$requests->count()} complaint(s) moved to \"Not contacted\".");

        return self::SUCCESS;
    }
}
