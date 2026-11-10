<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Services\ServiceRequestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkServiceRequestsSpamCommand extends Command
{
    /**
     * Phone numbers to mark as spam. Any format is fine — each one is
     * normalized before matching, the same way form submissions are.
     *
     * @var array<int, string>
     */
    private const PHONES = [
        '966500478729',
        '966500834455',
        '966501379797',
        '966501444120',
        '966501445545',
        '966502781511',
        '966505924808',
        '966506594962',
        '966507584027',
        '966508884333',
        '966509133427',
        '966512345678',
        '966530305478',
        '966530536317',
        '966532832752',
        '966534412120',
        '966536066673',
        '966539255566',
        '966540818822',
        '966541497441',
        '966541861742',
        '966542040333',
        '966544391877',
        '966547235243',
        '966550551631',
        '966550553718',
        '966551276990',
        '966553451123',
        '966554270008',
        '966554647498',
        '966554888684',
        '966555054222',
        '966555059614',
        '966555127230',
        '966555482996',
        '966555589313',
        '966558290888',
        '966559332018',
        '966559550837',
        '966559873688',
        '966559919934',
        '966560808045',
        '966560957409',
        '966561010999',
        '966563353737',
        '966563355921',
        '966563414234',
        '966564118666',
        '966565555222',
        '966565690065',
        '966566062089',
        '966566444888',
        '966567850522',
        '966568358119',
        '966569003037',
        '966569178838',
        '966576589282',
        '966579782041',
        '966583364444',
        '966590663661',
        '966598586808',
    ];

    protected $signature = 'service-requests:mark-spam
                            {--dry-run : Preview what would be updated without persisting}';

    protected $description = 'Mark all service requests for a given list of phone numbers as spam, blocking those phones from future submissions';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry-run');

        $spamStatus = ServiceRequestStatus::spamBucket();

        if (! $spamStatus) {
            $this->error('No active status is flagged as the spam bucket. Configure one before running this command.');

            return self::FAILURE;
        }

        if (empty(self::PHONES)) {
            $this->error('No phone numbers configured. Add them to the PHONES constant in this command.');

            return self::FAILURE;
        }

        $notFound = [];
        $updatedByPhone = [];

        foreach (self::PHONES as $rawPhone) {
            $phone = ServiceRequestService::normalizePhone($rawPhone);

            $requests = ServiceRequest::query()->where('phone', $phone)->get();

            if ($requests->isEmpty()) {
                $notFound[] = $rawPhone;
                $this->warn("  ⚠  {$rawPhone} (normalized: {$phone}) — no matching requests found.");

                continue;
            }

            $updatedCount = 0;

            foreach ($requests as $request) {
                if ($request->status === $spamStatus->slug) {
                    continue;
                }

                if (! $isDry) {
                    $request->update(['status' => $spamStatus->slug]);
                }

                $updatedCount++;
            }

            $updatedByPhone[$phone] = $updatedCount;

            $this->line(sprintf(
                '  %s %s — %d request(s) %s spam.',
                $isDry ? '[DRY-RUN]' : '✓',
                $phone,
                $updatedCount,
                $isDry ? 'would be marked as' : 'marked as',
            ));
        }

        Log::channel('lead_spam')->info('Bulk service request spam marking run.', [
            'dry_run' => $isDry,
            'updated_by_phone' => $updatedByPhone,
            'not_found' => $notFound,
        ]);

        $this->info(sprintf(
            'Done. %d phone(s) processed, %d not found.',
            count(self::PHONES),
            count($notFound),
        ));

        return self::SUCCESS;
    }
}
