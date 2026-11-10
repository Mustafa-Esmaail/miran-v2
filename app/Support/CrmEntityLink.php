<?php

namespace App\Support;

use App\Models\Deal;
use App\Models\ServiceRequest;

class CrmEntityLink
{
    public static function editUrl(?string $entityType, ?int $entityId): ?string
    {
        if (! $entityType || ! $entityId) {
            return null;
        }

        return match ($entityType) {
            ServiceRequest::class => route('filament.admin.resources.service-requests.edit', $entityId),
            Deal::class => route('filament.admin.resources.deals.edit', $entityId),
            default => null,
        };
    }
}
