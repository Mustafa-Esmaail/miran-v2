<?php

namespace App\Observers;

use App\Enums\CertificateStatus;
use App\Jobs\Nelc\SyncEarnedJob;
use App\Models\Certificate;

class CertificateObserver
{
    public function updated(Certificate $certificate): void
    {
        if (! $certificate->wasChanged('status')) {
            return;
        }

        if ($certificate->status === CertificateStatus::Approved) {
            SyncEarnedJob::dispatch($certificate->id);
        }
    }
}
