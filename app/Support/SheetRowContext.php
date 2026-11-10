<?php

namespace App\Support;

/**
 * Request-scoped carrier for extra data that belongs in the Google Sheets row
 * but is not stored on the service request (e.g. the landing page slug).
 *
 * ServiceRequestService sets it just before creating the request and clears it
 * right after; ServiceRequestObserver reads it while syncing the row.
 */
class SheetRowContext
{
    private ?string $landingSlug = null;

    public function setLandingSlug(?string $slug): void
    {
        $this->landingSlug = filled($slug) ? $slug : null;
    }

    public function landingSlug(): ?string
    {
        return $this->landingSlug;
    }

    public function clear(): void
    {
        $this->landingSlug = null;
    }
}
