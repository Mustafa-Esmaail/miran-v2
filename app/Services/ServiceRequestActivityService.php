<?php

namespace App\Services;

use App\Enums\ServiceRequestActivityType;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\User;
use Illuminate\Support\Arr;

class ServiceRequestActivityService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        ServiceRequest $request,
        ServiceRequestActivityType $type,
        ?string $from = null,
        ?string $to = null,
        ?string $note = null,
        array $meta = [],
        ?int $actorUserId = null,
    ): ServiceRequestActivity {
        $activity = ServiceRequestActivity::query()->create([
            'service_request_id' => $request->id,
            'actor_user_id' => $this->resolveActorUserId($actorUserId),
            'type' => $type,
            'from_value' => $from,
            'to_value' => $to,
            'note' => $note,
            'meta' => Arr::whereNotNull($meta),
            'created_at' => now(),
        ]);

        $request->forceFill(['last_activity_at' => now()])->saveQuietly();

        return $activity;
    }

    /**
     * Guards against a stale auth session (e.g. the user row was deleted after
     * login) crashing the insert on the actor_user_id foreign key.
     */
    private function resolveActorUserId(?int $actorUserId): ?int
    {
        if ($actorUserId === null) {
            return null;
        }

        return User::query()->whereKey($actorUserId)->exists() ? $actorUserId : null;
    }
}
