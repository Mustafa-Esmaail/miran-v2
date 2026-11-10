<?php

namespace App\Observers;

use App\Jobs\Nelc\SyncRegisteredJob;
use App\Models\CertificateRequest;

class CertificateRequestObserver
{
    public function created(CertificateRequest $certificateRequest): void
    {
        SyncRegisteredJob::dispatch($certificateRequest->id);
    }
}
