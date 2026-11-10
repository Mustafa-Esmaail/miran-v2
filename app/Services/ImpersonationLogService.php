<?php

namespace App\Services;

use App\Enums\ImpersonationEventType;
use App\Models\ImpersonationLog;
use Illuminate\Support\Arr;

class ImpersonationLogService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        ?int $adminId,
        ?int $studentId,
        ImpersonationEventType $eventType,
        ?string $routeName = null,
        ?string $url = null,
        ?string $method = null,
        array $meta = [],
    ): ImpersonationLog {
        return ImpersonationLog::query()->create([
            'admin_id' => $adminId,
            'student_id' => $studentId,
            'event_type' => $eventType,
            'route_name' => $routeName,
            'url' => $url,
            'method' => $method,
            'meta' => Arr::whereNotNull($meta),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
