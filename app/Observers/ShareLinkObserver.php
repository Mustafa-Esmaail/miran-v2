<?php

namespace App\Observers;

use App\Enums\CrmActivityType;
use App\Models\CrmActivity;
use App\Models\ShareLink;

class ShareLinkObserver
{
    public function created(ShareLink $link): void
    {
        $employee = $link->employee;
        $creator = $link->createdBy;

        CrmActivity::create([
            'entity_type' => ShareLink::class,
            'entity_id'   => $link->id,
            'type'        => CrmActivityType::Note,
            'subject'     => 'Share link created',
            'body'        => implode(' — ', array_filter([
                "Employee: {$employee?->name} ({$employee?->employee_code})",
                "Target: {$link->target_type}" . ($link->target_label ? " → {$link->target_label}" : ''),
                "URL: {$link->full_url}",
                $creator ? "Created by: {$creator->name}" : null,
            ])),
            'employee_id' => $link->employee_id,
            'created_at'  => now(),
        ]);
    }

    public function deleted(ShareLink $link): void
    {
        $employee = $link->employee;

        CrmActivity::create([
            'entity_type' => ShareLink::class,
            'entity_id'   => $link->id,
            'type'        => CrmActivityType::Note,
            'subject'     => 'Share link deleted',
            'body'        => implode(' — ', array_filter([
                "Employee: {$employee?->name} ({$employee?->employee_code})",
                "Target: {$link->target_type}" . ($link->target_label ? " → {$link->target_label}" : ''),
                "URL was: {$link->full_url}",
            ])),
            'employee_id' => $link->employee_id,
            'created_at'  => now(),
        ]);
    }
}
